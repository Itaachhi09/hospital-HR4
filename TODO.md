# TODO for Restoring Notification Icon Functionality

## Overview
This TODO tracks the steps to make the notification bell icon fully functional: dynamic badge with unread count, toggle dropdown on click, fetch and render notifications, mark as read on individual clicks, and navigate via links. No new files will be added; edits are limited to existing ones.

## Steps
- [x] Update `js/notifications/notifications.js`: Enhance `updateNotificationDot` to dynamically set the badge text (show exact count if ≤99, "99+" otherwise; empty/hide if 0). Confirm `renderNotificationList` and `handleNotificationItemClick` logic for styling, marking read, and refetching. Also, modify `fetchAndRenderNotifications` and `renderNotificationList` to support rendering to a custom target element (for full-page notifications view), export `handleNotificationItemClick` for reuse.
- [x] Edit `admin_landing.php`: Remove the inline `onclick="onNotificationDropdownOpen()"` from the bell button to prevent conflicts with main.js listener. Initialize `#notification-dot` with empty innerHTML and add `hidden` class for dynamic control.
- [x] Edit `js/main.js`: Update `displayNotificationsSection` to render notifications to `#notifications-full-list` using the new target parameter, add click listener for marking read on full page, and enable refresh button with target.
- [x] Test the implementation: Verified via user screenshots and code review that core functionality works (dynamic badge hides for 0 unread, dropdown toggles/renders, "View All" navigates to full page with proper rendering/refresh/click handling). Further DB-based testing skipped per user request.
