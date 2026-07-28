<?php

declare(strict_types=1);

namespace App\Support\Rail;

final class RailFlashStore
{
    private const KEY = '_rail_flash';

    public function __construct(private array &$session)
    {
    }

    public function add(string $type, string $message): void
    {
        $allowed = ['success', 'warning', 'error'];
        $this->session[self::KEY][] = [
            'type' => in_array($type, $allowed, true) ? $type : 'error',
            'message' => $message,
        ];
    }

    public function consume(): array
    {
        $messages = $this->session[self::KEY] ?? [];
        unset($this->session[self::KEY]);

        return is_array($messages) ? $messages : [];
    }
}
