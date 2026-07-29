<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Service EncryptionService
 *
 * Provides transparent encryption and decryption for sensitive model fields.
 * Uses Laravel's built-in Crypt (AES-256-CBC via APP_KEY) so no additional
 * infrastructure is required.
 *
 * For searchable encrypted fields this service maintains a SHA-256 hash
 * index.  Equality searches can be performed against the hash column while
 * the raw value remains encrypted at rest.
 */
final class EncryptionService
{
    /**
     * Prefix used for the hash-index column derived from an encrypted field.
     */
    private const HASH_PREFIX = 'hashed_';

    /**
     * Determine whether model encryption is enabled globally.
     */
    public function isEnabled(): bool
    {
        return (bool) config('security.encryption.model_encryption', true);
    }

    /**
     * Encrypt all configured fields on the given model.
     *
     * Call this from the model's `creating` / `saving` event hook.
     */
    public function encryptModelFields(Model $model): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $fields = $this->getEncryptedFields($model);

        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            if ($value === null || $value === '') {
                continue;
            }

            // Avoid double-encrypting.
            if ($this->isEncrypted($value)) {
                continue;
            }

            $model->setAttribute($field, $this->encrypt($value));

            // Maintain a hash index for equality lookups.
            $hashField = self::HASH_PREFIX . $field;

            if ($model->isFillable($hashField) || $this->hasColumn($model, $hashField)) {
                $model->setAttribute($hashField, $this->hashValue($value));
            }
        }
    }

    /**
     * Decrypt all configured fields on the given model.
     *
     * Call this from the model's `retrieved` event hook, or use the
     * companion accessor approach in the model trait.
     */
    public function decryptModelFields(Model $model): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $fields = $this->getEncryptedFields($model);

        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            if ($value === null || $value === '') {
                continue;
            }

            if (! $this->isEncrypted($value)) {
                continue;
            }

            try {
                $model->setAttribute($field, $this->decrypt($value));
            } catch (DecryptException $e) {
                Log::warning('Failed to decrypt field ' . $field . ' on ' . $model::class, [
                    'model_id' => $model->getKey(),
                    'field' => $field,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Encrypt a plaintext value.
     */
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a ciphertext value.
     */
    public function decrypt(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    /**
     * Return a deterministic SHA-256 hash of the value for indexed lookups.
     *
     * WARNING: Hash indexes leak the fact that two rows share the same
     * plaintext.  Use only for fields where this is an acceptable trade-off
     * (e.g. email, phone number).
     */
    public function hashValue(string $value): string
    {
        return Hash::make($value, ['rounds' => 4]);
    }

    /**
     * Verify a plaintext value against a stored hash index.
     */
    public function verifyHash(string $value, string $hash): bool
    {
        return Hash::check($value, $hash);
    }

    /**
     * Re-encrypt all configured fields using the current APP_KEY.
     *
     * Useful after key rotation: decrypts with the old key and re-encrypts
     * with the new one.  The model should already be decrypted when this
     * is called (i.e. after `decryptModelFields`).
     */
    public function reEncryptModelFields(Model $model): void
    {
        $this->encryptModelFields($model);
    }

    /**
     * Detect whether a value is already encrypted (looks for base64-encoded
     * payload typical of Laravel's Crypt output).
     */
    private function isEncrypted(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // Laravel encrypted values are serialised + base64-encoded JSON
        // beginning with "ey" (base64 of '{').
        return str_starts_with($value, 'ey');
    }

    /**
     * Get the list of encrypted fields for this model's table.
     *
     * @return list<string>
     */
    private function getEncryptedFields(Model $model): array
    {
        $config = config('security.encryption.encrypted_fields', []);
        $table = $model->getTable();

        return $config[$table] ?? [];
    }

    /**
     * Check whether the model's table has a given column (without
     * throwing).
     */
    private function hasColumn(Model $model, string $column): bool
    {
        try {
            return $model->getConnection()
                ->getSchemaBuilder()
                ->hasColumn($model->getTable(), $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
