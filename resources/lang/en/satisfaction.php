<?php

return [
    // General
    'title' => 'Satisfaction Survey',
    'survey_detail' => 'Survey Detail',
    'survey_list' => 'Survey List',
    'recent_surveys' => 'Recent Surveys',
    'score' => 'Score',
    'anonymous' => 'Anonymous',

    // Statistics
    'overall_rating' => 'Overall Rating',
    'completed_surveys' => 'Completed Surveys',
    'pending_surveys' => 'Pending Surveys',
    'avg_rating' => 'Average Rating',
    'reviews' => 'reviews',

    // Ratings
    'rating' => 'Rating',
    'rating_breakdown' => 'Rating Breakdown',
    'rating_distribution' => 'Rating Distribution',
    'ratings' => [
        'overall' => 'Overall Satisfaction',
        'service' => 'Service Attitude',
        'environment' => 'Environment & Facilities',
        'wait_time' => 'Wait Time',
        'doctor' => 'Doctor Service',
    ],

    // Status
    'status' => [
        'label' => 'Status',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'expired' => 'Expired',
    ],

    // NPS
    'would_recommend' => 'Would Recommend (0-10)',
    'nps_types' => [
        'promoter' => 'Promoter',
        'passive' => 'Passive',
        'detractor' => 'Detractor',
    ],

    // Survey Info
    'survey_info' => 'Survey Information',
    'channel' => 'Survey Channel',
    'channels' => [
        'sms' => 'SMS',
        'wechat' => 'WeChat',
        'app' => 'APP',
        'instore' => 'In-Store',
    ],
    'date' => 'Survey Date',
    'created_at' => 'Sent At',
    'branch' => 'Branch',
    'patient' => 'Patient',
    'doctor' => 'Doctor',
    'appointment_date' => 'Appointment Date',

    // Feedback
    'feedback' => 'Feedback',
    'suggestions' => 'Suggestions',
    'no_feedback' => 'No feedback provided',
    'no_suggestions' => 'No suggestions provided',

    // Actions
    'send_batch' => 'Send Batch',
    'select_date' => 'Select Appointment Date',
    'resend' => 'Resend',
    'resend_hint' => 'Patient has not responded. You can resend the survey.',
    'resend_not_implemented' => 'Feature in development',

    // Messages
    'survey_sent' => 'Survey has been sent',
    'batch_sent' => 'Sent :count surveys',
    'thank_you' => 'Thank you for your feedback!',
    'awaiting_response' => 'Awaiting patient response...',

    // Rankings
    'doctor_ranking' => 'Doctor Rating Ranking',
    'monthly_trend' => 'Monthly Trend',

    // ── Public patient-facing form ─────────────────────────────────
    'fill_title'  => 'Visit Satisfaction Survey',
    'fill_intro'  => 'Thank you for your visit. Please rate your experience — your feedback helps us improve.',
    'nps_help'    => '0 = would not recommend at all, 10 = would strongly recommend',
    'star_hints'  => [
        1 => 'Very dissatisfied',
        2 => 'Dissatisfied',
        3 => 'Neutral',
        4 => 'Satisfied',
        5 => 'Very satisfied',
    ],
    'feedback_placeholder'     => 'How was your overall experience? (optional)',
    'suggestions_placeholder'  => 'What could we do better? (optional)',
    'submit_anonymously'       => 'Submit anonymously (hide my name)',
    'submit'                   => 'Submit',
    'submitting'               => 'Submitting...',
    'overall_rating_required'  => 'Please rate your overall satisfaction first',
    'network_error'            => 'Network error, please try again later',
    'thank_you_sub'            => 'Your feedback has been received. Thank you.',

    // ── Link state ─────────────────────────────────────────────────
    'link_invalid'      => 'Link no longer valid',
    'link_invalid_hint' => 'This survey link does not exist, has already been completed, or has expired. Please contact the clinic front desk if you still wish to respond.',
    'link_expired'      => 'This survey link has expired',
    'already_completed' => 'This survey has already been completed and cannot be submitted again',
    'link_regenerated'  => 'A new link has been generated; the previous one is now invalid',

    // ── Back-office distribution ───────────────────────────────────
    'batch_generated'  => ':count survey link(s) generated',
    'copy_link'        => 'Copy survey link',
    'link_copied'      => 'Link copied — paste it into WeChat to send to the patient',
    'copy_failed'      => 'Copy failed, please select and copy the link manually',
    'fill_link'        => 'Survey link',
    'link_expires_at'  => 'Valid until :time',
    'no_link_yet'      => 'No survey link generated yet',
];
