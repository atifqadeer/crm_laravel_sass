<?php

/*
|--------------------------------------------------------------------------
| Sale Requirement Pickers (defaults)
|--------------------------------------------------------------------------
|
| Default options for the timing, experience, benefits and qualification
| chips on the sale form. The live options are stored in the database and
| managed from Administrator > Sale Requirements; these defaults are seeded
| by the migration and used by its "Restore defaults" button.
|
| The form composes the selection into one clean sentence ("<prefix> A, B and C.")
| which is stored in the matching sales column and served to the job portal.
|
| - prefix:    default statement placed before the selected values (editable on the form)
| - lead:      optional single-choice group placed first in the sentence
| - connector: text joining the lead choice and the selected options
| - hours:     show an "hours per week" input (timing only)
|
*/

return [

    'timing' => [
        'label' => 'Timing',
        'icon' => 'solar:clock-circle-bold-duotone',
        'hint' => 'Select every shift pattern on offer.',
        'prefix' => 'Working hours:',
        'required' => true,
        'hours' => true,
        'groups' => [
            'Shift pattern' => [
                'Day Shifts',
                'Night Shifts',
                'Long Days (12 hrs)',
                'Early Shifts',
                'Late Shifts',
                'Rotational Shifts',
            ],
            'Schedule' => [
                'Monday to Friday',
                'Weekends',
                'Flexible Hours',
                'Contracted Hours',
                'Bank / Ad-hoc',
            ],
        ],
    ],

    'experience' => [
        'label' => 'Experience',
        'icon' => 'solar:medal-ribbons-star-bold-duotone',
        'hint' => 'Choose the minimum level, then the areas of experience.',
        'prefix' => 'Experience required:',
        'required' => true,
        'connector' => 'in',
        'lead' => [
            'title' => 'Minimum experience',
            'options' => [
                ['label' => 'Not essential', 'text' => 'Not essential, full training provided', 'connector' => '; experience in', 'suffix' => 'is an advantage'],
                ['label' => '6+ months', 'text' => 'Minimum 6 months'],
                ['label' => '1+ year', 'text' => 'Minimum 1 year'],
                ['label' => '2+ years', 'text' => 'Minimum 2 years'],
                ['label' => '3+ years', 'text' => 'Minimum 3 years'],
                ['label' => '5+ years', 'text' => 'Minimum 5 years'],
            ],
        ],
        'groups' => [
            'Experience in' => [
                'UK Healthcare',
                'Care Home',
                'Nursing Home',
                'Hospital / NHS',
                'Elderly Care',
                'Dementia Care',
                'Learning Disabilities',
                'Mental Health',
                'Paediatrics',
                'Supervisory / Management',
                'Commercial Kitchen',
                'Early Years / Childcare',
            ],
        ],
    ],

    'benefits' => [
        'label' => 'Benefits',
        'icon' => 'solar:gift-bold-duotone',
        'hint' => 'Highlight what makes this role attractive.',
        'prefix' => 'Benefits include:',
        'required' => true,
        'groups' => [
            'Pay & leave' => [
                'Competitive Pay',
                'Weekly Pay',
                'Overtime Available',
                'Enhanced Weekend Rates',
                'Bonus Scheme',
                'Paid Annual Leave',
                'Paid Breaks',
                'Workplace Pension',
            ],
            'Growth' => [
                'Paid Training',
                'Career Progression',
                'Free DBS',
                'Revalidation Support',
                'Visa Sponsorship',
            ],
            'Perks' => [
                'Free Uniform',
                'Free Meals on Shift',
                'Free Parking',
                'Relocation Support',
                'Accommodation Support',
                'Refer-a-Friend Bonus',
                'Employee Discounts',
                'Wellbeing Support',
            ],
        ],
    ],

    'qualification' => [
        'label' => 'Qualification',
        'icon' => 'solar:diploma-verified-bold-duotone',
        'hint' => 'List the qualifications and checks candidates must hold.',
        'prefix' => 'Qualifications required:',
        'required' => true,
        'groups' => [
            'Nursing' => [
                'NMC Registered Nurse (RGN)',
                'NMC Registered Nurse (RMN)',
                'NMC Registered Nurse (RNLD)',
                'Active NMC PIN',
            ],
            'Care' => [
                'Care Certificate',
                'NVQ Level 2 Health & Social Care',
                'NVQ Level 3 Health & Social Care',
                'Level 5 Leadership & Management',
            ],
            'Kitchen' => [
                'Food Hygiene Level 2',
                'Food Hygiene Level 3',
                'NVQ Professional Cookery',
            ],
            'Early years' => [
                'Level 2 Early Years',
                'Level 3 Early Years',
                'Paediatric First Aid',
            ],
            'General' => [
                'Right to Work in the UK',
                'Enhanced DBS',
                'Full UK Driving Licence',
                'GCSE English & Maths',
            ],
        ],
    ],

];
