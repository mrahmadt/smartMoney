<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Helpers\fireflyIII;

class dummyTransactions extends Seeder
{
    public $minTransactions = 1000;
    public $maxTransactions = 2000;
    
    public $minAmount = 5;
    public $maxAmount = 15000;

    public $periodUnit = 'months'; // months, years, days
    
    public $periodAmount = 50;

    public $createTransactionMode = 'recurring'; // 'regular', 'recurring'

    public $types = ['withdrawal', 'deposit'];

    public $recurringTypes = [
        'daily',
        'weekly',
        'monthly',
        'quarterly',
        'half-year',
        'yearly'
    ];
    
    public $recurring_destination_names = [
        'Phone Company',
        'Gym Membership',
        'Car Insurance',
        'Health Insurance',
        // 'Rent',
        // 'Mortgage',
        // 'Internet Service',
        // 'Electricity',
    ];

    public $recurringType = 'monthly'; // 'daily', 'weekly', 'monthly', 'yearly'
    public $recurring_destination_name = 'Phone Company';

    // for recurring transactions
    public $randomDays = 2; // days to randomly adjust the date
    public $randomAmountPercentage = 1; // amount in percentage to randomly adjust the amount

    public $categories = [
        null,
        "Groceries",
        "Dining",
        "Transportation",
        "Utilities",
        "Rent/Mortgage",
        "Insurance",
        "Health & Wellness",
        "Entertainment",
        "Shopping",
        "Travel",
        "Education",
        "Charity/Donations",
        "Personal Care",
        "Investments",
        "Loans/Debts",
        "Taxes",
        "Home Improvement",
        "Gifts/Donations",
        "Subscriptions/Memberships",
        "Miscellaneous/Other"
    ];

    public $tags = [
        null,
        'Work',
        'Home Office',
        "Healthy Habits",
        "Adventure",
        "Indulgence",
        "Tech Gadgets",
        "Eco-Friendly",
        "Self-Care",
        "DIY Projects",
        "Pet Supplies",
        "Art & Culture",
        "Fitness & Wellness",
        "Wanderlust",
        "Family Time",
        "Mindfulness",
        "Career Growth",
        "Charity",
        "Gaming",
        "Fashion & Style",
        "Bookworm",
        "Outdoor Fun",
        "Creative Pursuits"
    ];


    
    public function run(): void
    {

        $this->recurringTypes = explode(',', config('billDetector.transactions_recurring_types'));

        if($this->createTransactionMode == 'regular'){
            $this->regularTransactions();

        }elseif($this->createTransactionMode == 'recurring'){
            $this->recurringTransactions();
        }
    }

    public function recurringTransactions(){
        $type = 'withdrawal';
        $fireflyIII = new fireflyIII();
        $accounts = $fireflyIII->getAccounts();
        $accounts = $accounts->data;

        $recurringTypes = $this->recurringTypes;

        foreach($this->recurring_destination_names as $name){
            $index = array_rand($recurringTypes);
            $this->recurringType = $recurringTypes[$index];
            unset($recurringTypes[$index]);
            if(!count($recurringTypes)){
                $recurringTypes = $this->recurringTypes;
            }

            print 'Creating recurring transactions for ' . $name . ' ' . $this->recurringType . "\n";


            $this->recurring_destination_name = $name . ' ' . $this->recurringType; //fake()->words(3, true);
            $transactionsData = $this->generateRecurringTransactions();
            $totalTransactions = count($transactionsData);
            for($i=0; $i<$totalTransactions; $i++){
                $category = $this->categories[array_rand($this->categories)];
                $account = $accounts[array_rand($accounts)];

                $options = [];
                $options['type'] = $type;
                $options['description'] = fake()->sentence() . ' ' . $this->recurringType;

                if(mt_rand(0, 4) >= 1){
                    $options['category_name'] = $category;
                }

                $options['tags'] = $this->recurringType;

                $options['destination_name'] = $this->recurring_destination_name;
                $options['source_id'] = $account->id;
                $options['amount'] = $transactionsData[$i]['amount'];
                $options['date'] = ($transactionsData[$i]['date']);
                $data = $fireflyIII->newTransaction($options);
                // print $data->data->id."\n";
            }
        }
    }


    public function generateRecurringTransactions(){
        $transactions = [];

        $amount = mt_rand($this->minAmount, $this->maxAmount);
        $randomDaysUp = $this->randomDays;
        $randomDaysDown = $this->randomDays * -1;
        $randomAmountPercentageUp = $this->randomAmountPercentage;
        $randomAmountPercentageDown = $this->randomAmountPercentage * -1;

        // Get the current date
        $currentDate = new \DateTime();

        // Loop through the periodAmount and generate transactions
        for ($i = 0; $i < $this->periodAmount; $i++) {
            // Modify the current date based on the recurringType
            switch ($this->recurringType) {
                case 'daily':
                    $currentDate->modify('-1 day');
                    break;
                case 'weekly':
                    $currentDate->modify('-1 week');
                    break;
                case 'monthly':
                    $currentDate->modify('-1 month');
                    break;
                case 'quarterly':
                    $currentDate->modify('-3 months');
                    break;
                case 'half-year':
                    $currentDate->modify('-6 months');
                    break;
                case 'yearly':
                    $currentDate->modify('-1 year');
                    break;
            }

            // Randomly adjust the date within a range of +/- 4 days
            $randomDays = rand($randomDaysDown, $randomDaysUp);
            if($randomDays >= 1){
                $currentDate->modify("+$randomDays days");
            }elseif($randomDays <= -1){
                $currentDate->modify("$randomDays days");
            }

            // Randomly adjust the amount within a range of +/- 5%
            $randomAmount = $amount + ($amount * (rand($randomAmountPercentageDown, $randomAmountPercentageUp) / 100));

            // Add the transaction to the array
            $transactions[] = [
                'date' => $currentDate->format('c'),
                'amount' => round($randomAmount, 2),
            ];
        }

        return $transactions;
    }

    public function regularTransactions(){
        $fireflyIII = new fireflyIII();
        $totalTransactions = mt_rand($this->minTransactions, $this->maxTransactions);

        $accounts = $fireflyIII->getAccounts();
        $accounts = $accounts->data;


        for($i=0; $i<$totalTransactions; $i++){
            $type = $this->types[array_rand($this->types)];
            $category = $this->categories[array_rand($this->categories)];
            $tag = $this->tags[array_rand($this->tags)];
            $amount = mt_rand($this->minAmount, $this->maxAmount);
            $account = $accounts[array_rand($accounts)];

            $options = [];
            $options['type'] = $type;
            $options['description'] = fake()->sentence();

            if(mt_rand(0, 4) >= 1){
                $options['category_name'] = $category;
            }

            if(mt_rand(0, 4) >= 1){
                $options['tags'] = $tag;
            }

            if($type == 'withdrawal'){
                $options['destination_name'] = fake()->words(3, true);
                $options['source_id'] = $account->id;
            }else{
                $options['source_name'] = fake()->words(3, true);
                $options['destination_id'] = $account->id;
            }
            $date = $this->getDateFromPast($this->periodUnit, $this->periodAmount);

            $options['date'] = $date;
            $options['amount'] = $amount;
            $options['notes'] = fake()->paragraph();
            $data = $fireflyIII->newTransaction($options);
        }
    }

    function getDateFromPast($unit, $amount) {
        // Create a new DateTime object for the current date
        $date = new \DateTime();
    
        // Modify the date based on the specified unit and amount
        switch ($unit) {
            case 'months':
                $date->modify('-' . $amount . ' months');
                break;
            case 'years':
                $date->modify('-' . $amount . ' years');
                break;
            case 'days':
                $date->modify('-' . $amount . ' days');
                break;
            default:
                // If an invalid unit is provided, return null
                return null;
        }
    
        // Return the date in the desired format
        return $date->format('c');
    }
}
