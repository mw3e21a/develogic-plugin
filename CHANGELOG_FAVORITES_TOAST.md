# Changelog: Favorites Toast & Obserwowane View

## Overview
Added toast notifications and favorites-only view functionality to the apartments list template, matching the style provided by the user.

## Changes Made

### 1. CSS Updates (`assets/css/apartments-list.css`)

#### Toast Notification Styles
- Added `.toast-container` for positioning toast notifications
- Added `.toast` with slide-in/out animations using `cubic-bezier` easing
- Added `.toast-icon` with green checkmark
- Added `.toast-content`, `.toast-title`, and `.toast-link` styling
- Responsive adjustments for mobile devices

#### Favorites View Toggle
- Added `.favorites-toggle-container` for button layout
- Added `.favorites-toggle-btn` with active states
- Added `.favorites-count` styling
- Added `.apartment-list.hide-favorites` to hide non-favorite items

#### Share Buttons
- Added `.favorites-share-container` for share buttons layout
- Added `.share-btn` with platform-specific colors (Twitter blue, Facebook blue, Email gray)
- Added hover effects that fill background on hover
- Responsive layout that stacks on mobile

### 2. Template Updates (`templates/apartments-list.php`)

#### Favorites Toggle UI
- Added toggle buttons container with "Wszystkie" and "Obserwowane" options
- Added favorites count display
- Only shown when `show_favorite` is enabled

#### Toast Container
- Added empty `.toast-container` div for JavaScript-generated toasts

#### Share Buttons UI
- Added share buttons container with Twitter, Facebook, and Email icons
- Only visible when in favorites view
- Left border separator on desktop, top border on mobile

### 3. JavaScript Updates (`assets/js/apartments-list.js`)

#### Toast Notification Functionality
- Added `showToast()` function that:
  - Creates toast element with icon and content
  - Animates in with slide effect
  - Auto-dismisses after 4 seconds
  - Includes "Zobacz listę" link that switches to favorites view
  - Properly cleans up DOM element

#### Favorites Toggle Functionality
- Added `setupFavoritesViewToggle()` function:
  - Handles button click events
  - Toggles active states
  - Adds/removes `hide-favorites` class to filter list
- Added `updateFavoritesCount()` function to display current count

#### Enhanced Favorite Toggle
- Updated `toggleFavorite()` to:
  - Mark apartment items with `is-favorite` class
  - Show toast notification when adding (not when removing)
  - Update favorites count after changes
- Updated `loadFavoritesState()` to properly mark favorites on page load

#### Initialization
- Added calls to `setupFavoritesViewToggle()` and `updateFavoritesCount()` in `init()`

#### Share Functionality
- Added `setupShareButtons()` function to handle share button clicks
- Added `generateShareLink()` function to create shareable URLs with favorites
- Added `shareFavorites()` function for Twitter, Facebook, and Email sharing:
  - Twitter: Opens tweet composer with link
  - Facebook: Opens Facebook sharer dialog
  - Email: Opens email client with pre-filled message
- Added `checkSharedFavorites()` function to:
  - Parse favorites from URL parameter
  - Add shared favorites to localStorage
  - Automatically switch to favorites view
  - Show toast notification with count
- Updated `setupFavoritesViewToggle()` to show/hide share buttons

## Features

### Toast Notification
- Shows when user clicks "Obserwuj" (favorite) button
- Displays "Dodano do obserwowanych" message with checkmark icon
- Includes "Zobacz listę" link to immediately switch to favorites view
- Auto-dismisses after 4 seconds
- Smooth slide-in/out animations

### Favorites View
- Two buttons: "Wszystkie" (All) and "Obserwowane" (Favorites)
- Active button highlighted with blue background
- Favorites count shows current number of favorites
- Clicking "Obserwowane" hides all non-favorite apartments
- Clicking "Wszystkie" shows all apartments again
- State persists across page loads via localStorage
- Share buttons appear when viewing favorites

### Share Functionality
- **Twitter**: Opens tweet composer with shareable link
- **Facebook**: Opens Facebook share dialog
- **Email**: Opens email client with pre-filled message
- Shareable links include favorites as URL parameter
- When link is opened, favorites are automatically added to recipient's list
- Toast notification confirms how many apartments were added
- Automatically switches to favorites view when receiving shared link

## Usage

The features are automatically enabled when using the `[develogic_apartments_list]` shortcode with `show_favorite="true"` or when the default setting is enabled.

No additional configuration needed. All functionality works out of the box.

## Browser Compatibility
- Modern browsers with localStorage support
- CSS animations with `cubic-bezier` fallback
- Touch support for mobile devices

## Notes
- Toast notifications only appear when adding favorites (not removing)
- Favorites are stored in localStorage with key `develogic_favorites`
- The favorites view toggle is only visible when `show_favorite` is enabled
- All animations use CSS transforms for better performance
- Share buttons only visible in favorites view
- Shareable URLs use format: `?favorites=id1,id2,id3`
- Shared favorites are merged with existing favorites (no duplicates)
- Empty favorites list cannot be shared (shows alert)
- All sharing opens in new windows/email clients

