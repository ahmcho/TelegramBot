<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client\Traits;

use AhmCho\Telegram\Exception\HttpClientException;

/**
 * Multipart Request Trait
 *
 * Shared helpers for HTTP clients that need to detect and upload local
 * files (CURLFile values) alongside regular Telegram API parameters.
 */
trait MultipartRequestTrait
{
    /**
     * @param array<string, mixed> $params
     */
    private function hasFileUpload(array $params): bool
    {
        foreach ($params as $value) {
            if ($value instanceof \CURLFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a multipart/form-data request body for params containing
     * one or more CURLFile values.
     *
     * @param array<string, mixed> $params
     * @return array{body: string, boundary: string}
     */
    private function buildMultipartBody(array $params): array
    {
        $boundary = '----TelegramBotBoundary' . bin2hex(random_bytes(16));
        $body = '';

        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }

            $body .= $value instanceof \CURLFile
                ? $this->buildFilePart($boundary, (string) $name, $value)
                : $this->buildFieldPart($boundary, (string) $name, $value);
        }

        $body .= "--{$boundary}--\r\n";

        return ['body' => $body, 'boundary' => $boundary];
    }

    private function buildFieldPart(string $boundary, string $name, mixed $value): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '';
        } else {
            $value = (string) $value;
        }

        return "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n"
            . "{$value}\r\n";
    }

    private function buildFilePart(string $boundary, string $name, \CURLFile $file): string
    {
        $path = $file->getFilename();
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new HttpClientException("Unable to read local file for upload: {$path}");
        }

        $filename = $file->getPostFilename() !== '' ? $file->getPostFilename() : basename($path);
        $mimeType = $file->getMimeType() !== '' ? $file->getMimeType() : 'application/octet-stream';

        return "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n"
            . "Content-Type: {$mimeType}\r\n\r\n"
            . "{$contents}\r\n";
    }
}
