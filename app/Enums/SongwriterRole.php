<?php

namespace App\Enums;

enum SongwriterRole: string
{
    case Author = 'author';
    case Composer = 'composer';
    case AuthorComposer = 'author_composer';

    public function label(): string
    {
        return __('songwriter.roles.' . $this->value);
    }

    /**
     * value => label, for rendering <select> option lists.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }
        return $options;
    }
}
