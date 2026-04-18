<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InputSanitizeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! method_exists($request, 'setGlobal')) {
            return null;
        }

        $request->setGlobal('get', $this->sanitizeArray((array) $request->getGet(), 'get'));
        $request->setGlobal('post', $this->sanitizeArray((array) $request->getPost(), 'post'));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function sanitizeArray(array $data, string $scope): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $field = is_string($key) ? strtolower($key) : '';

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value, $scope);
                continue;
            }

            if (! is_string($value)) {
                $clean[$key] = $value;
                continue;
            }

            $clean[$key] = $this->sanitizeString($value, $field, $scope);
        }

        return $clean;
    }

    private function sanitizeString(string $value, string $field, string $scope): string
    {
        $normalized = str_replace("\0", '', $value);
        $normalized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $normalized) ?? $normalized;

        if (! $this->isPasswordField($field)) {
            $normalized = trim($normalized);
            $normalized = strip_tags($normalized);
        }

        $maxLength = $this->fieldMaxLength($field, $scope);
        if (function_exists('mb_substr')) {
            $normalized = mb_substr($normalized, 0, $maxLength);
        } else {
            $normalized = substr($normalized, 0, $maxLength);
        }

        return $normalized;
    }

    private function isPasswordField(string $field): bool
    {
        return in_array($field, ['password', 'confirm_password', 'new_password', 'old_password'], true);
    }

    private function fieldMaxLength(string $field, string $scope): int
    {
        if ($this->isPasswordField($field)) {
            return 255;
        }

        return match ($field) {
            'email' => 254,
            'first_name', 'last_name', 'name' => 80,
            'student_id', 'account_id', 'receipt_id', 'student_no' => 64,
            'course', 'year_level', 'house', 'title' => 120,
            'message', 'description' => 4000,
            default => $scope === 'get' ? 1000 : 2000,
        };
    }
}

