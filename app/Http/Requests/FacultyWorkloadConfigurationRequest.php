<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyWorkloadConfigurationRequest extends FormRequest
{
    private const WEEK_DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('program_head');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $programId = auth()->user()->program_id;
        $userId = $this->input('user_id');
        $method = $this->method();

        return [
            'user_id' => [
                'required',
                'exists:users,id',
                $method === 'POST' 
                    ? Rule::unique('faculty_workload_configurations')
                        ->where('program_id', $programId) 
                    : Rule::unique('faculty_workload_configurations')
                        ->where('program_id', $programId)
                        ->ignore($this->route('faculty_workload_configuration')),
            ],
            'contract_type' => 'required|in:Full-Time,Part-Time,Contractual',
            'max_lecture_hours' => 'required|integer|min:1|max:99',
            'max_lab_hours' => 'required|integer|min:0|max:99',
            'max_hours_per_day' => 'required|integer|min:1|max:24',
            'available_days' => 'nullable|array|max:7',
            'available_days.*' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'teaching_scheme' => 'required|array',
            'teaching_scheme.*.enabled' => 'nullable|boolean',
            'teaching_scheme.*.start' => 'nullable|date_format:H:i',
            'teaching_scheme.*.end' => 'nullable|date_format:H:i',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ];
    }

    /**
     * Configure additional custom validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $teachingScheme = $this->input('teaching_scheme', []);
            $enabledDays = [];

            foreach (self::WEEK_DAYS as $day) {
                $dayConfig = $teachingScheme[$day] ?? [];
                $enabled = filter_var($dayConfig['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (!$enabled) {
                    continue;
                }

                $enabledDays[] = $day;

                $start = $dayConfig['start'] ?? null;
                $end = $dayConfig['end'] ?? null;

                if (!$start || !$end) {
                    $validator->errors()->add("teaching_scheme.$day", "{$day}: start and end time are required when enabled.");
                    continue;
                }

                $startTs = strtotime($start);
                $endTs = strtotime($end);

                if ($startTs === false || $endTs === false || $startTs >= $endTs) {
                    $validator->errors()->add("teaching_scheme.$day", 'Start time must be earlier than end time.');
                }
            }

            if (empty($enabledDays)) {
                $validator->errors()->add('teaching_scheme', 'Please enable at least one teaching day.');
            }
        });
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'This faculty already has a workload configuration.',
            'user_id.required' => 'Please select a faculty member.',
            'user_id.exists' => 'The selected faculty does not exist.',
            'contract_type.required' => 'Please select a contract type.',
            'max_lecture_hours.required' => 'Maximum lecture hours per week is required.',
            'max_lecture_hours.min' => 'Maximum lecture hours must be at least 1.',
            'max_lab_hours.required' => 'Maximum laboratory hours per week is required.',
            'max_lab_hours.min' => 'Maximum laboratory hours cannot be negative.',
            'max_hours_per_day.required' => 'Maximum hours per day is required.',
            'max_hours_per_day.min' => 'Maximum hours per day must be at least 1.',
            'available_days.required' => 'Please select at least one available day.',
            'available_days.min' => 'Please select at least one available day.',
            'teaching_scheme.required' => 'Please configure at least one teaching day.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }

    /**
     * Get sanitized input
     */
    public function sanitized()
    {
        $rawScheme = $this->input('teaching_scheme', []);
        $teachingScheme = [];

        foreach (self::WEEK_DAYS as $day) {
            $dayConfig = $rawScheme[$day] ?? [];
            $enabled = filter_var($dayConfig['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$enabled) {
                continue;
            }

            $start = $dayConfig['start'] ?? null;
            $end = $dayConfig['end'] ?? null;

            if (!$start || !$end) {
                continue;
            }

            $teachingScheme[$day] = [
                'start' => date('H:i', strtotime($start)),
                'end' => date('H:i', strtotime($end)),
            ];
        }

        return [
            'user_id' => (int)$this->input('user_id'),
            'contract_type' => trim($this->input('contract_type')),
            'max_lecture_hours' => (int)$this->input('max_lecture_hours'),
            'max_lab_hours' => (int)$this->input('max_lab_hours'),
            'max_hours_per_day' => (int)$this->input('max_hours_per_day'),
            'available_days' => array_keys($teachingScheme),
            'teaching_scheme' => $teachingScheme,
            'start_time' => $this->input('start_time') ? date('H:i:s', strtotime($this->input('start_time'))) : null,
            'end_time' => $this->input('end_time') ? date('H:i:s', strtotime($this->input('end_time'))) : null,
        ];
    }
}
