<?php

namespace App\Enums;

/**
 * Статус студента на занятии. Единый источник правды:
 * используется в UI журнала, экспорте и расчёте автомата.
 *
 * Эмодзи и смыслы — по легенде из реального файла «Посещения».
 */
enum AttendanceStatus: string
{
    case Present          = 'present';           // + просто был
    case LabDone          = 'lab_done';          // ✅ сдал лабу
    case LabHalf          = 'lab_half';          // 🐤 сдана половина
    case CheckedIn        = 'checked_in';        // 🙋 просто отметился
    case Questions        = 'questions';         // ❓ вопросы
    case SeriousQuestions = 'serious_questions'; // ‼️ серьёзные вопросы
    case Cried            = 'cried';             // 🥲 наплакано
    case Ai               = 'ai';                // 🔥 ай

    public function emoji(): string
    {
        return match ($this) {
            self::Present          => '+',
            self::LabDone          => '✅',
            self::LabHalf          => '🐤',
            self::CheckedIn        => '🙋',
            self::Questions        => '❓',
            self::SeriousQuestions => '‼️',
            self::Cried            => '🥲',
            self::Ai               => '🔥',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Present          => 'Был',
            self::LabDone          => 'Сдал лабу',
            self::LabHalf          => 'Сдана половина',
            self::CheckedIn        => 'Просто отметился',
            self::Questions        => 'Вопросы',
            self::SeriousQuestions => 'Серьёзные вопросы',
            self::Cried            => 'Наплакано',
            self::Ai               => 'Ай',
        };
    }

    /** Любой проставленный статус = студент присутствовал на занятии. */
    public function countsAsVisit(): bool
    {
        return true;
    }

    /** Этот статус означает сдачу лабы (учитывается в расчёте автомата). */
    public function isLabDone(): bool
    {
        return $this === self::LabDone;
    }

    /** Список для UI: [value => ['emoji','label']]. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = ['emoji' => $c->emoji(), 'label' => $c->label()];
        }
        return $out;
    }
}
