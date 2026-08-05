<?php

namespace App\Http\Requests\Admin;

use App\Models\Proposal;
use App\Models\Service;
use App\Services\Services\PublicServiceTaxonomy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProposalProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    public function rules(): array
    {
        $taxonomy = app(PublicServiceTaxonomy::class);

        return [
            'service_category' => ['required', Rule::in($taxonomy->codes())],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_category' => __('site.form_public_service_category'),
            'service_id' => __('site.form_choose_service'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $proposal = $this->route('proposal');

                if (! $proposal instanceof Proposal) {
                    return;
                }

                if (! $proposal->project()->exists() && ! $proposal->isProjectConvertible()) {
                    $validator->errors()->add('proposal', __('site.proposal_project_not_eligible'));
                }

                if (! $proposal->clientDisplayName() || $proposal->clientDisplayName() === __('site.unassigned')) {
                    $validator->errors()->add('proposal', __('site.proposal_project_missing_client_name'));
                }

                if (! filter_var($proposal->clientDisplayEmail(), FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('proposal', __('site.proposal_project_missing_client_email'));
                }

                $serviceId = $this->input('service_id');
                $category = $this->input('service_category');

                if (! $serviceId || ! $category) {
                    return;
                }

                $service = Service::query()->where('is_active', true)->find($serviceId);

                if (! $service) {
                    return;
                }

                if ($service->publicCategoryCode() !== $category) {
                    $validator->errors()->add('service_id', __('site.proposal_project_service_category_mismatch'));
                }
            },
        ];
    }
}
