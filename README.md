## Introducing smartMoney!

smartMoney is a suite of scripts and plugins designed to enhance your experience with [FireFly-III](https://firefly-iii.org/ "FireFly-III"). Developed out of the necessity to streamline transaction creation in FireFly-III, this project aims to simplify financial management processes.

### Key Features

#### Automatic SMS Parsing and Transaction Creation
Utilize [SMSReceiver-iOS](https://github.com/mrahmadt/SMSReceiver-iOS "SMSReceiver-iOS") to seamlessly forward your bank SMS messages to smartMoney. Features include:
- SMS parsing using regular expressions
- SMS parsing using ChatGPT
- Transaction categorization using ChatGPT
- create transaction in FireFly

#### Simple Budget Web Application
Access a straightforward web application to view specific budgets and track withdrawals and deposits. Ideal for sharing accounts with others or monitoring home expenses without sharing FireFly-III admin credentials.

![IMG_2867](https://github.com/mrahmadt/smartMoney/assets/957921/9bdd2583-2cb9-4fe6-bd9c-8808c8d894fe)





#### Alert Notifications
Receive push notifications on your mobile device via Web Push notification or Telegram integration.

#### Detection of Abnormal Transactions
Identify withdrawals exceeding the average amount from expense accounts or categories. (**Pending implementation of actual alerts**)

#### Automatic Subscription Detection
Detect subscriptions on various intervals (daily, weekly, monthly, etc.) and automatically create bills in FireFly-III, facilitating the linking of bills to future transactions.

#### Subscription Amount Increase Alerts
Alerts for increases in subscription amounts (**Pending Implementation**).





### Installation Guide
Check out our [Wiki Pages](https://github.com/mrahmadt/smartMoney/wiki)
