<?php

namespace App\Services;

class MeetingCodeGenerator
{
    private const PREFIX = 'MEET';
    private const SEGMENTS = 3;
    private const SEGMENT_LENGTH = 4;
    private const CHARS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function generate(): string
    {
        $code = $this->generateCode();
        
        // Ensure uniqueness
        while (\App\Models\Meeting::where('meeting_code', $code)->exists()) {
            $code = $this->generateCode();
        }
        
        return $code;
    }

    private function generateCode(): string
    {
        $segments = [];
        
        for ($i = 0; $i < self::SEGMENTS; $i++) {
            $segment = '';
            for ($j = 0; $j < self::SEGMENT_LENGTH; $j++) {
                $segment .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
            }
            $segments[] = $segment;
        }
        
        return self::PREFIX . '-' . implode('-', $segments);
    }

    public function parse(string $code): ?array
    {
        if (!preg_match('/^' . self::PREFIX . '-([' . self::CHARS . ']{' . self::SEGMENT_LENGTH . '})-([' . self::CHARS . ']{' . self::SEGMENT_LENGTH . '})-([' . self::CHARS . ']{' . self::SEGMENT_LENGTH . '})$/', $code, $matches)) {
            return null;
        }
        
        return [
            'prefix' => $matches[0],
            'segments' => [$matches[1], $matches[2], $matches[3]],
        ];
    }
}