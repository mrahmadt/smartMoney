<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Cafe', 'category_prompt' => 'coffee shops (Starbucks, Dunkin, Tim Hortons, Costa, Caribou)'],
            ['name' => 'Groceries', 'category_prompt' => 'supermarkets, grocery stores (Tamimi, Panda, Carrefour, Danube, Lulu, Othaim, BinDawood)'],
            ['name' => 'Shopping', 'category_prompt' => 'department stores, general retail (Target, Walmart, Costco, Sam\'s Club, SACO)'],
            ['name' => 'Dining', 'category_prompt' => 'restaurants, fast food (Burger King, McDonald\'s, KFC, Herfy, Kudu, Pizza Hut, Dominos, Shawarmer)'],
            ['name' => 'Transportation', 'category_prompt' => 'ride-hailing (Uber, Careem), fuel stations (Aldrees, Petromin, Naft), parking, toll fees'],
            ['name' => 'Utilities', 'category_prompt' => 'electricity (SEC), water (NWC), telecom (STC, Mobily, Zain)'],
            ['name' => 'Healthcare', 'category_prompt' => 'pharmacies (CVS, Nahdi, Al-Dawaa, Walgreens), hospitals, clinics, medical labs, dentists'],
            ['name' => 'Entertainment', 'category_prompt' => 'cinemas (AMC, VOX, Muvi), gaming, theme parks, streaming (Netflix, Spotify, Apple, Disney+, YouTube Premium), app stores'],
            ['name' => 'Online Shopping', 'category_prompt' => 'e-commerce (Amazon, Noon, Shein, AliExpress, Jarir, eXtra)'],
            ['name' => 'Education', 'category_prompt' => 'schools, universities, courses, bookstores, tutoring, training'],
            ['name' => 'Travel', 'category_prompt' => 'airlines (Saudia, flynas, Air Arabia), hotels, booking platforms (Booking.com, Airbnb), car rentals'],
            ['name' => 'Personal Care', 'category_prompt' => 'salons, barbers, spas, beauty products, perfumes'],
            ['name' => 'Clothing', 'category_prompt' => 'fashion stores (Zara, H&M, Centrepoint, Max, Nike, Adidas)'],
            ['name' => 'Home & Furniture', 'category_prompt' => 'IKEA, Home Centre, Pottery Barn, home maintenance'],
            ['name' => 'Government', 'category_prompt' => 'government fees, Absher, MOI, traffic fines, visa fees'],
            ['name' => 'Insurance', 'category_prompt' => 'car insurance, medical insurance, travel insurance'],
            ['name' => 'Charity', 'category_prompt' => 'donations, Zakah, sadaqah'],
            ['name' => 'Cash', 'category_prompt' => 'ATM withdrawals'],
            ['name' => 'Transfer', 'category_prompt' => 'bank transfers between accounts'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'category_prompt' => $category['category_prompt'],
                    'enable_prompt' => true,
                ]
            );
        }
    }
}
