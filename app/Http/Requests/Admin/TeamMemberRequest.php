<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        $teamMemberId = $this->route('teamMember')?->id;

        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('team_members', 'slug')->ignore($teamMemberId)],
            'role' => ['required', 'string', 'max:180'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'expertise' => ['nullable', 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function normalizedSlug(): string
    {
        return Str::slug($this->validated('slug') ?: $this->validated('name'));
    }

    public function lines(string $field): array
    {
        return collect(preg_split('/\R+/', (string) $this->validated($field), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
