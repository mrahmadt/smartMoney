

[alerts]
Send Weekly Report

Yes. The setup is not hard on a Mac.

Because you want iPhone + Flutter/Dart + VS Code, the right setup is:
	•	VS Code for most coding
	•	Flutter SDK which includes Dart tools
	•	Xcode for iOS build, simulator, signing, and the SMS filter extension
	•	CocoaPods for iOS dependencies

Flutter’s current docs recommend installing Flutter and using VS Code for editing/debugging, and for iOS support they point to Xcode and CocoaPods.  ￼

What you need to install

Install these first:
	1.	Xcode
	•	install from the Mac App Store
	•	open it once and accept the license
	•	Xcode is needed for Apple platform builds, simulators, signing, and extension targets.  ￼
	2.	Xcode command line tools
Run:

xcode-select --install

Flutter’s install guide lists the Xcode command-line tools as a prerequisite on macOS.  ￼

	3.	VS Code
Install normal VS Code.
	4.	Flutter extension in VS Code
Install the Flutter extension. It also installs the Dart extension.  ￼
	5.	Flutter SDK
You can install it through VS Code’s Flutter flow, or manually. Flutter’s docs now support a quick VS Code-based install path.  ￼
	6.	CocoaPods
Flutter’s iOS setup says you should install the latest CocoaPods for plugins that use native iOS code.  ￼

The easiest setup path

Open Terminal and do this first:

xcode-select --install

Then install Homebrew if you do not already have it. After that:

brew install cocoapods
brew install --cask visual-studio-code

Then install Xcode from the App Store and open it once.

After that, open VS Code and install the Flutter extension.

Then in VS Code:
	•	press Cmd + Shift + P
	•	type Flutter: New Project
	•	let VS Code guide the Flutter SDK install if prompted

Flutter’s quick install flow in VS Code supports starting from Flutter: New Project, and the Flutter extension installs Dart support too.  ￼

Verify the setup

Run this in Terminal:

flutter doctor

This checks whether Flutter, Xcode, CocoaPods, and iOS tooling are ready. Flutter’s install docs use flutter doctor as the standard verification step.  ￼

If something is missing, flutter doctor will usually tell you exactly what to fix.

Create your first app

In VS Code:
	•	open Command Palette
	•	run Flutter: New Project
	•	choose Application
	•	choose a folder
	•	enter app name, for example family_alerts

Or from Terminal:

flutter create family_alerts
cd family_alerts
code .

Then start it:

flutter run

Run it on iPhone or simulator

For simulator:
	•	open Xcode
	•	start an iPhone simulator
	•	then from project folder run:

flutter run



For real iPhone:
	•	connect the iPhone by cable
	•	open ios/Runner.xcworkspace in Xcode once
	•	set your Apple Developer team under Signing
	•	trust the developer profile on the phone if asked
	•	then run from VS Code or Terminal

Xcode is still needed for signing and Apple-specific setup even if you write most code in VS Code.  ￼

For your app specifically

Your app has 3 parts:

1. Main app

Build this in Flutter/Dart inside VS Code.

This can handle:
	•	login
	•	settings
	•	open URL
	•	showing alerts
	•	registering device token for push

2. Push notifications

This needs Apple push setup in Xcode and Apple Developer portal.

You can still write app-side handling in Flutter, but APNs capability and signing are Apple-side work. APNs registration is part of Apple’s notification flow.  ￼

3. SMS filter extension

This part should be created as a native iOS extension target in Xcode, not pure Dart.

So your normal flow will be:
	•	build main app in VS Code
	•	open Xcode only when you need:
	•	signing
	•	entitlements
	•	app groups
	•	push capability
	•	SMS filter extension target

What I recommend you install now

Run these in order:

xcode-select --install
brew install cocoapods
brew install --cask visual-studio-code

Then:
	•	install Xcode from App Store
	•	open Xcode once
	•	open VS Code
	•	install Flutter extension
	•	run:

flutter doctor



What tools you actually need

Minimum tools:
	•	VS Code
	•	Flutter extension
	•	Flutter SDK
	•	Xcode
	•	Xcode command-line tools
	•	CocoaPods

Nice to have:
	•	Git
	•	GitHub Copilot
	•	iOS Simulator
	•	an actual iPhone for real testing

One important note

Since you want an iOS app with an SMS filter extension, this is not a pure Dart-only workflow.
It is:
	•	mostly Dart/Flutter in VS Code
	•	some Swift and Xcode work for the extension and Apple capabilities

That is normal.

If you want, I can give you the exact next step with:
a full beginner setup guide for your Mac, then create the Flutter app, then add push, then add the SMS filter extension step by step.