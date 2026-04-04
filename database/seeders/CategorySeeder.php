<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Cafe', 'category_prompt' => 'coffee shops (Starbucks, Dunkin, Tim Hortons, Costa, Caribou)', 'translations' => ['ar' => 'مقهى']],
            ['name' => 'Groceries', 'category_prompt' => 'supermarkets, grocery stores (Tamimi, Panda, Carrefour, Danube, Lulu, Othaim, BinDawood)', 'translations' => ['ar' => 'بقالة']],
            ['name' => 'Shopping', 'category_prompt' => 'department stores, general retail (Target, Walmart, Costco, Sam\'s Club, SACO)', 'translations' => ['ar' => 'تسوق']],
            ['name' => 'Dining', 'category_prompt' => 'restaurants, fast food (Burger King, McDonald\'s, KFC, Herfy, Kudu, Pizza Hut, Dominos, Shawarmer)', 'translations' => ['ar' => 'مطاعم']],
            ['name' => 'Transportation', 'category_prompt' => 'ride-hailing (Uber, Careem), fuel stations (Aldrees, Petromin, Naft), parking, toll fees', 'translations' => ['ar' => 'مواصلات']],
            ['name' => 'Utilities', 'category_prompt' => 'electricity (SEC), water (NWC), telecom (STC, Mobily, Zain)', 'translations' => ['ar' => 'خدمات']],
            ['name' => 'Healthcare', 'category_prompt' => 'pharmacies (CVS, Nahdi, Al-Dawaa, Walgreens), hospitals, clinics, medical labs, dentists', 'translations' => ['ar' => 'صحة']],
            ['name' => 'Entertainment', 'category_prompt' => 'cinemas (AMC, VOX, Muvi), gaming, theme parks, streaming (Netflix, Spotify, Apple, Disney+, YouTube Premium), app stores', 'translations' => ['ar' => 'ترفيه']],
            ['name' => 'Online Shopping', 'category_prompt' => 'e-commerce (Amazon, Noon, Shein, AliExpress, Jarir, eXtra)', 'translations' => ['ar' => 'تسوق إلكتروني']],
            ['name' => 'Education', 'category_prompt' => 'schools, universities, courses, bookstores, tutoring, training', 'translations' => ['ar' => 'تعليم']],
            ['name' => 'Travel', 'category_prompt' => 'airlines (Saudia, flynas, Air Arabia), hotels, booking platforms (Booking.com, Airbnb), car rentals', 'translations' => ['ar' => 'سفر']],
            ['name' => 'Personal Care', 'category_prompt' => 'salons, barbers, spas, beauty products, perfumes', 'translations' => ['ar' => 'عناية شخصية']],
            ['name' => 'Clothing', 'category_prompt' => 'fashion stores (Zara, H&M, Centrepoint, Max, Nike, Adidas)', 'translations' => ['ar' => 'ملابس']],
            ['name' => 'Home & Furniture', 'category_prompt' => 'IKEA, Home Centre, Pottery Barn, home maintenance', 'translations' => ['ar' => 'منزل وأثاث']],
            ['name' => 'Government', 'category_prompt' => 'government fees, Absher, MOI, traffic fines, visa fees', 'translations' => ['ar' => 'حكومة']],
            ['name' => 'Insurance', 'category_prompt' => 'car insurance, medical insurance, travel insurance', 'translations' => ['ar' => 'تأمين']],
            ['name' => 'Charity', 'category_prompt' => 'donations, Zakah, sadaqah', 'translations' => ['ar' => 'صدقة']],
            ['name' => 'Cash', 'category_prompt' => 'ATM withdrawals', 'translations' => ['ar' => 'سحب نقدي']],
            ['name' => 'Transfer', 'category_prompt' => 'bank transfers between accounts', 'translations' => ['ar' => 'تحويل']],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'category_prompt' => $category['category_prompt'],
                    'enable_prompt' => true,
                    'translations' => $category['translations'],
                ]
            );
        }
    }
}
