<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JoiningRequest;
use App\Models\User;

$storageDir = storage_path('app/public/joining_docs');
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// 1. Candidate Photo SVG
$photoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="480" viewBox="0 0 400 480"><rect width="400" height="480" fill="#1e293b"/><circle cx="200" cy="180" r="80" fill="#3b82f6"/><circle cx="200" cy="150" r="45" fill="#f8fafc"/><path d="M120 340 C120 260 280 260 280 340 Z" fill="#f8fafc"/><text x="200" y="390" font-size="20" font-weight="bold" fill="#ffffff" text-anchor="middle">ARJUN YADAV</text><text x="200" y="420" font-size="14" fill="#94a3b8" text-anchor="middle">Starforce Facilities KYC Photo</text></svg>';
file_put_contents($storageDir . '/candidate_photo_arjun.svg', $photoSvg);

// 2. PAN Card SVG
$panSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="520" height="320" viewBox="0 0 520 320"><rect width="520" height="320" rx="16" fill="#eff6ff" stroke="#3b82f6" stroke-width="3"/><rect x="20" y="20" width="480" height="44" rx="8" fill="#1e3a8a"/><text x="260" y="48" font-size="16" font-weight="bold" fill="#ffffff" text-anchor="middle">INCOME TAX DEPARTMENT - GOVT OF INDIA</text><rect x="40" y="85" width="100" height="120" fill="#cbd5e1" rx="6"/><text x="160" y="115" font-size="14" font-weight="bold" fill="#0f172a">Permanent Account Number Card</text><text x="160" y="150" font-size="20" font-weight="bold" font-family="monospace" fill="#1e40af">ABCDE1234F</text><text x="160" y="185" font-size="13" fill="#334155">Name: ARJUN YADAV</text><text x="160" y="210" font-size="13" fill="#334155">Father: RAMESH YADAV</text><text x="160" y="235" font-size="13" fill="#334155">DOB: 15/08/1997</text></svg>';
file_put_contents($storageDir . '/pan_card_arjun.svg', $panSvg);

// 3. Aadhaar Card SVG
$aadhaarSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="520" height="320" viewBox="0 0 520 320"><rect width="520" height="320" rx="16" fill="#fefce8" stroke="#ca8a04" stroke-width="3"/><rect x="20" y="20" width="480" height="44" rx="8" fill="#854d0e"/><text x="260" y="48" font-size="16" font-weight="bold" fill="#ffffff" text-anchor="middle">UNIQUE IDENTIFICATION AUTHORITY OF INDIA</text><rect x="40" y="85" width="100" height="120" fill="#cbd5e1" rx="6"/><text x="160" y="115" font-size="14" font-weight="bold" fill="#0f172a">Aadhaar - Mera Aadhaar, Meri Pehchan</text><text x="160" y="155" font-size="22" font-weight="bold" font-family="monospace" fill="#b45309">4892 5621 8904</text><text x="160" y="190" font-size="13" fill="#334155">ARJUN YADAV</text><text x="160" y="215" font-size="13" fill="#334155">DOB: 1997-08-15 | Male</text></svg>';
file_put_contents($storageDir . '/aadhaar_card_arjun.svg', $aadhaarSvg);

// 4. Bank Passbook SVG
$bankSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="520" height="320" viewBox="0 0 520 320"><rect width="520" height="320" rx="16" fill="#f0fdf4" stroke="#16a34a" stroke-width="3"/><rect x="20" y="20" width="480" height="44" rx="8" fill="#14532d"/><text x="260" y="48" font-size="16" font-weight="bold" fill="#ffffff" text-anchor="middle">STATE BANK OF INDIA - PASSBOOK COPY</text><text x="40" y="105" font-size="13" font-weight="bold" fill="#0f172a">A/C HOLDER: ARJUN YADAV</text><text x="40" y="135" font-size="16" font-weight="bold" font-family="monospace" fill="#15803d">A/C NO: 389201948201</text><text x="40" y="165" font-size="13" fill="#334155">IFSC: SBIN0004521 (Noida Branch)</text><text x="40" y="195" font-size="13" fill="#334155">MICR: 110002154 | Branch Code: 04521</text><text x="40" y="225" font-size="12" fill="#15803d">Status: ACTIVE &amp; KYC VERIFIED</text></svg>';
file_put_contents($storageDir . '/bank_passbook_arjun.svg', $bankSvg);

// Create or update candidate user
$user = User::firstOrCreate(
    ['email' => 'arjun.yadav@starforce.in'],
    [
        'name' => 'Arjun Yadav',
        'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        'phone' => '9871234560',
        'status' => 'active',
    ]
);

$docs = [
    [
        'title' => 'Aadhaar Card',
        'file_name' => 'aadhaar_card_arjun.svg',
        'file_url' => url('/storage/joining_docs/aadhaar_card_arjun.svg'),
        'relative_url' => '/storage/joining_docs/aadhaar_card_arjun.svg',
        'file_size' => '1.2 MB',
        'mime_type' => 'image/svg+xml',
        'uploaded_at' => now()->toIso8601String(),
    ],
    [
        'title' => 'PAN Card',
        'file_name' => 'pan_card_arjun.svg',
        'file_url' => url('/storage/joining_docs/pan_card_arjun.svg'),
        'relative_url' => '/storage/joining_docs/pan_card_arjun.svg',
        'file_size' => '860 KB',
        'mime_type' => 'image/svg+xml',
        'uploaded_at' => now()->toIso8601String(),
    ],
    [
        'title' => 'Bank Passbook / Cancelled Cheque',
        'file_name' => 'bank_passbook_arjun.svg',
        'file_url' => url('/storage/joining_docs/bank_passbook_arjun.svg'),
        'relative_url' => '/storage/joining_docs/bank_passbook_arjun.svg',
        'file_size' => '1.4 MB',
        'mime_type' => 'image/svg+xml',
        'uploaded_at' => now()->toIso8601String(),
    ],
    [
        'title' => 'Educational Certificate',
        'file_name' => 'graduation_degree_delhi_university.pdf',
        'file_url' => url('/storage/joining_docs/doc_1792148457_5adae729.pdf'),
        'relative_url' => '/storage/joining_docs/doc_1792148457_5adae729.pdf',
        'file_size' => '2.1 MB',
        'mime_type' => 'application/pdf',
        'uploaded_at' => now()->toIso8601String(),
    ],
    [
        'title' => 'Previous Employment Proof',
        'file_name' => 'g4s_relieving_certificate.pdf',
        'file_url' => url('/storage/joining_docs/doc_1792148457_5adae729.pdf'),
        'relative_url' => '/storage/joining_docs/doc_1792148457_5adae729.pdf',
        'file_size' => '940 KB',
        'mime_type' => 'application/pdf',
        'uploaded_at' => now()->toIso8601String(),
    ],
    [
        'title' => 'Police Verification / Character Certificate',
        'file_name' => 'delhi_police_clearance_certificate.pdf',
        'file_url' => url('/storage/joining_docs/doc_1792148457_5adae729.pdf'),
        'relative_url' => '/storage/joining_docs/doc_1792148457_5adae729.pdf',
        'file_size' => '1.1 MB',
        'mime_type' => 'application/pdf',
        'uploaded_at' => now()->toIso8601String(),
    ],
];

// Create or update Joining Request in pending state
$joining = JoiningRequest::updateOrCreate(
    ['user_id' => $user->id],
    [
        'status' => 'pending',
        'proposed_employee_code' => 'SF-P1048',
        'date_of_joining' => '2026-10-20',
        'proposed_designation' => 'Security Field Supervisor',
        'proposed_department' => 'Operations & Field Services',
        'branch_location' => 'Noida Cyber Park Cluster',
        'reporting_officer' => 'Sanjay Verma (Branch Head)',

        'full_name' => 'Arjun Yadav',
        'father_spouse_name' => 'Ramesh Yadav',
        'dob' => '1997-08-15',
        'gender' => 'Male',
        'marital_status' => 'Married',
        'blood_group' => 'B+',
        'phone' => '9871234560',
        'alternate_phone' => '9818765432',
        'email' => 'arjun.yadav@starforce.in',
        'aadhaar_number' => '4892 5621 8904',
        'pan_number' => 'ABCDE1234F',
        'photo_url' => url('/storage/joining_docs/candidate_photo_arjun.svg'),

        'permanent_address' => 'House No. 42, Sector 12, Noida, Gautam Buddha Nagar, UP - 201301',
        'current_address' => 'House No. 42, Sector 12, Noida, Gautam Buddha Nagar, UP - 201301',

        'emergency_contact_name' => 'Ramesh Yadav',
        'emergency_relationship' => 'Father',
        'emergency_phone' => '9818765432',
        'emergency_address' => 'House No. 42, Sector 12, Noida, UP',

        'account_holder_name' => 'Arjun Yadav',
        'bank_name' => 'State Bank of India',
        'branch_name' => 'Sector 12 Noida Branch',
        'account_number' => '389201948201',
        'ifsc_code' => 'SBIN0004521',
        'upi_id' => '9871234560@sbi',

        'qualification' => 'Graduation (Bachelor of Arts)',
        'institution_board' => 'Delhi University',
        'passing_year' => '2023',
        'percentage_grade' => '72%',

        'previous_company_name' => 'G4S Security Solutions',
        'previous_designation' => 'Senior Field Guard',
        'period_of_employment' => '2 Years (2024-2026)',
        'reason_for_leaving' => 'Career growth & supervisory role',
        'previous_salary' => 22000,

        'documents_submitted' => [
            'Aadhaar Card',
            'PAN Card',
            'Bank Passbook / Cancelled Cheque',
            'Educational Certificate',
            'Previous Employment Proof',
            'Police Verification / Character Certificate',
        ],
        'uploaded_documents' => $docs,

        'declaration_accepted' => true,
        'signature_name' => 'Arjun Yadav',
        'declaration_date' => '2026-10-16',
        'declaration_place' => 'Noida',
    ]
);

echo "Successfully seeded Arjun Yadav KYC Joining Application ID: " . $joining->id . "\n";
