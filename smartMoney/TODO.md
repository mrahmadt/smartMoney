
[account notes]
user_id=1
SMS_Sender=SMS Sender name
SMS_AcctCodes= formated in comma
SMS_Options=json format
# SMS_Options={"transactions_budget_id": 2, "3ka99": {"transactions_budget_id": 5}} 


[alerts]
about invalid SMS or error in job and web controller
send message when transaction created
Send Weekly Report




TODOs
[ ] edit accounts settings in our UI
[ ] SubscriptionDetector
[ ] Alert Average Transaction
[ ] alert via email, telegram, whatsapp, pushover?!

[ ] alert of not able to do currency change




abnormalTransaction
- Type 
-- withdrawal
-- transfer

- Via 
-- Category
-- Budget
-- Destination
-- Source
-- Type


- Check
-- transaction_journal_id


average_transactions_months
abnormal_threshold_percentage_withdrawal
abnormal_threshold_percentage_source
abnormal_threshold_percentage_destination
abnormal_threshold_percentage_category




	•	charge at unusual hour









in #sym:CategoryMapping , create a json column (null), allow user to create to add more alternative categories, user can select multiple categories as alternatives for this store and the alternatve categories id will be stored in this json column

also add an icon button "Suggest Alternateve Categories", when user click, it should ask AI (with good prompt include example of stores already mapped to this category as example and ask the AI to suggest few categories like 1-4 max), be aware maybe some categories will not have stores mapped to it, so we will need to send the category name to the AI to suggest. the prompt should explain to the AI what we need. make sure the UI indecate that we are working one it (the AI call). create laravel AI agent if required.



then, when we create a transaction with this category, we will have a new menu in the UI, when user click on it, it will show all recent transactions that has alternative categories and click in alternative category and it will update the transaction in FireFly III with the alternative category . it should be inline click to change the transaction. after clicking alternative category this transaction show be removed from this UI list. user can also dismss if they don't want to change the category
give also the user option to change the default category for this store, it will change the default store mapping and change the transaction (only this transaction, not the old transactions)

the menu show also show a badge number of how many transaction we have


this new functionality should be available to all users. but we should show the user only the account he own or the budget he own

except for user_id=1, they should see everything
