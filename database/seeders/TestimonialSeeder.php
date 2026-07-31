<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'patient_name' => 'Pn. Zainab',
                'patient_area' => 'Jitra',
                'quote' => 'Doktor sangat sabar terangkan setiap simptom. Anak-anak saya pun selesa datang ke sini.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'patient_name' => 'En. Faizal',
                'patient_area' => 'Kodiang',
                'quote' => 'Tak payah tunggu lama, staff mesra dan bilik konsultasi bersih & selesa.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'patient_name' => 'Pn. Aminah',
                'patient_area' => 'Changlun',
                'quote' => 'Dah jadi klinik pilihan sekeluarga sejak sekian lama. Waktu operasi yang panjang sangat membantu keluarga bekerja macam kami.',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::updateOrCreate(['patient_name' => $testimonial['patient_name']], $testimonial);
        }
    }
}
