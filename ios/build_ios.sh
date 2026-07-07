#!/bin/bash
# Script to build SOLOREEL for iOS Simulator bypassing XCFramework signature validation errors
# Useful for Xcode 15+ when using pre-compiled binaries from SPM (like GoogleMobileAds)

# Navigate to the directory where this script is located
cd "$(dirname "$0")"

echo "Building SOLOREEL for iOS Simulator..."
echo "Using flag: -disable-xcframework-signature-validation"

xcodebuild clean build \
    -project SOLOREEL.xcodeproj \
    -scheme SOLOREEL \
    -sdk iphonesimulator \
    -configuration Release \
    CODE_SIGN_IDENTITY="" \
    CODE_SIGNING_REQUIRED=NO \
    CODE_SIGNING_ALLOWED=NO \
    -disable-xcframework-signature-validation \
    -destination "generic/platform=iOS Simulator"

if [ $? -eq 0 ]; then
    echo "=================================================="
    echo "✅ BUILD SUCCEEDED!"
    echo "=================================================="
else
    echo "=================================================="
    echo "❌ BUILD FAILED."
    echo "If it failed with 'IDERunDestination: Supported platforms for the buildables in the current scheme is empty.',"
    echo "try specifying an exact device destination by editing this script."
    echo "For example: -destination 'platform=iOS Simulator,name=iPhone 15 Pro'"
    echo "=================================================="
fi
