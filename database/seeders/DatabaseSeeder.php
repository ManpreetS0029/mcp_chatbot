<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create or update default Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // 2. Initialize 7 Working Days
        $daysConfig = [
            'monday' => true,
            'tuesday' => true,
            'wednesday' => true,
            'thursday' => true,
            'friday' => true,
            'saturday' => true,
            'sunday' => false,
        ];

        foreach ($daysConfig as $day => $isOpen) {
            WorkingDay::updateOrCreate(
                ['day_of_week' => $day],
                ['is_open' => $isOpen]
            );
        }

        // 3. Create default recurring time slots for open days
        $weekdayTimes = [
            ['start' => '09:00:00', 'end' => '10:00:00'],
            ['start' => '10:00:00', 'end' => '11:00:00'],
            ['start' => '11:00:00', 'end' => '12:00:00'],
            ['start' => '14:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '16:00:00'],
            ['start' => '16:00:00', 'end' => '17:00:00'],
        ];

        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($weekdays as $day) {
            foreach ($weekdayTimes as $time) {
                TimeSlot::firstOrCreate(
                    [
                        'day_of_week' => $day,
                        'start_time' => $time['start'],
                        'end_time' => $time['end'],
                    ],
                    [
                        'capacity' => 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Saturday slots
        $saturdayTimes = [
            ['start' => '10:00:00', 'end' => '11:00:00'],
            ['start' => '11:00:00', 'end' => '12:00:00'],
            ['start' => '12:00:00', 'end' => '13:00:00'],
        ];
        foreach ($saturdayTimes as $time) {
            TimeSlot::firstOrCreate(
                [
                    'day_of_week' => 'saturday',
                    'start_time' => $time['start'],
                    'end_time' => $time['end'],
                ],
                [
                    'capacity' => 1,
                    'is_active' => true,
                ]
            );
        }

        // 4. Create a few sample appointments for testing / initial preview
        $tomorrow = Carbon::tomorrow();
        // find a slot for tomorrow's day of week
        $tomorrowDayName = strtolower($tomorrow->format('l'));
        $sampleSlot = TimeSlot::where('day_of_week', $tomorrowDayName)->first();

        if ($sampleSlot) {
            Appointment::firstOrCreate(
                ['customer_email' => 'sarah.connor@example.com', 'appointment_date' => $tomorrow->format('Y-m-d')],
                [
                    'booking_reference' => 'APT-DEMO1001',
                    'customer_name' => 'Sarah Connor',
                    'customer_phone' => '+1 (555) 019-2834',
                    'appointment_date' => $tomorrow->format('Y-m-d'),
                    'time_slot_id' => $sampleSlot->id,
                    'start_time' => $sampleSlot->start_time,
                    'end_time' => $sampleSlot->end_time,
                    'status' => Appointment::STATUS_CONFIRMED,
                    'notes' => 'Consultation regarding system requirements.',
                ]
            );
        }

        $nextWeek = Carbon::now()->addDays(3);
        $nextWeekDayName = strtolower($nextWeek->format('l'));
        $sampleSlot2 = TimeSlot::where('day_of_week', $nextWeekDayName)->skip(1)->first() ?? TimeSlot::where('day_of_week', $nextWeekDayName)->first();

        if ($sampleSlot2) {
            Appointment::firstOrCreate(
                ['customer_email' => 'alex.morgan@example.com', 'appointment_date' => $nextWeek->format('Y-m-d')],
                [
                    'booking_reference' => 'APT-DEMO1002',
                    'customer_name' => 'Alex Morgan',
                    'customer_phone' => '+1 (555) 482-9102',
                    'appointment_date' => $nextWeek->format('Y-m-d'),
                    'time_slot_id' => $sampleSlot2->id,
                    'start_time' => $sampleSlot2->start_time,
                    'end_time' => $sampleSlot2->end_time,
                    'status' => Appointment::STATUS_PENDING,
                    'notes' => 'Follow up discussion on appointment chatbot integration.',
                ]
            );
        }
    }
}
