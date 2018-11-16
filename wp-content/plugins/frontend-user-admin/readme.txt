=== Frontend User Admin ===
Contributors: ETNET Co., Ltd.
Tags: frontend, user, admin, management, member, members
Requires at least: 2.8
Tested up to: 4.9.6
Stable tag: 3.2.1

This plugin makes it possible to manage users in the frontend side.

== Description ==

The Frontend User Admin plugin makes it possible to manage users in the frontend side.

== Installation ==

1. Copy the `frontend-user-admin` directory into your `wp-content/plugins` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit the options in `User Management`
4. That's it! :)

== Known Issues / Bugs ==

== Frequently Asked Questions ==

== Changelog ==

= 3.2.1 =
* Option converter.
* Bugfix: AjaxZip2.

= 3.2 =
* Bugfix: order information in member conditions.

= 3.1.9 =
* Bugfix: order information in member conditions.

= 3.1.8 =
* Bugfix: order information in member conditions.

= 3.1.7 =
* Order information in member conditions.

= 3.1.6 =
* Bugfix: email duplication.

= 3.1.5 =
* Bugfix: user import.

= 3.1.4 =
* Bugfix: Number of Password.

= 3.1.3 =
* Option to send e-mail after the password locked.
* Number of Password.
* User import error output.

= 3.1.2 =
* Bugfix: default user attributes with requirement.

= 3.1.1 =
* Changes of the loading way of the install program.
* User role settings.
* Logout option for multisite.
* Bugfix: user import.

= 3.1 =
* Adds the hook before sending a registration email.

= 3.0.9 =
* reCAPTCHA.
* `redirect_to` attribute for the fua shortcode.
* Bugfix: user attribute conditions with the admin checked. 

= 3.0.8 =
* Bugfix: user attribute output on emails.

= 3.0.7 =
* Code cleaning.
* Bugfix: PHP7.
* Bugfix: value update of the display type for the admin panel.

= 3.0.6 =
* Bugfix: email confirmation.

= 3.0.5 =
* Option to update user attributes regardless of required fields in the admin.
* Changed maximum and minimum number of letters to the text input fields.
* Logout Time except Administrators.
* Apply the member conditions to the sub pages.

= 3.0.4 =
* Redirection attribute for each user: redirect_to

= 3.0.3 =
* Code cleaning.
* Bugfix: PHP7.

= 3.0.2 =
* Bugfix: user update in the password change.

= 3.0.1 =
* Bugfix: user extract.

= 3.0 =
* Full-text search in the user list.
* Bugfix: the user export and user log export of mass data.

= 2.9.8 =
* Bugfix: PHP7.

= 2.9.7 =
* Composite unique attribute.
* Bugfix: member condition in PHP7.

= 2.9.6 =
* Bugfix: user log summary in multisites.

= 2.9.5 =
* Code cleaning.
* Keeping the checkbox of the terms of use.

= 2.9.4 =
* Bugfix: keeping values which are not checked in the Profile Item and Order.

= 2.9.3 =
* `readonly` and `disabled` attributes for the user attributes.
* `display` type for the Field Type Admin.

= 2.9.2 =
* Bugfix: options not to send the password and email change emails.

= 2.9.1 =
* Bugfix: mails.

= 2.9 =
* Options not to send the password and email change emails.
* Option to output the item name by use of the field name in the user export.
* Email Confirmation (parallel registration).
* `datetime` format change for user attributes.
* Excerpt options.
* Unique registration in the multi site.
* Site domain and site title registration in the multi site.
* Email validation for user attributes.
* Option to disable the widget while nologin.
* Array value output by using [fuauc].
* Bugfix: output of the checkbox type in the new user notification email.

= 2.8.1 =
* `relation` attribute for [fualist] shortcode.
* Bugfix: password for WordPress 4.3.

= 2.8 =
* [fualist] shortcode.
* Value takeover on errors.
* Publicity settings for user attributes.
* File upload in the user registration.
* Code cleaning.
* Bugfix: user import.

= 2.7 =
* Judgement of Android smartphones.
* Bugfix: vanishing of display_name in the user import.
* Bugfix: member condition.
* Bugfix: lost password message.

= 2.6.8 =
* Bugfix: message css for the Twenty Fifteen theme.

= 2.6.7 =
* Delete option for the instant editor.
* Bugfix: inappropriate text in the user import.

= 2.6.6 =
* Option to send email to users in the user import.
* Code cleaning.
* Bugfix: temporary file delete in importing users.
* Bugfix: email confirmation (before registration).

= 2.6.5 =
* Bugfix: password retrieval.
* Bugfix: admin bar disappearance after updating the profile.

= 2.6.4 =
* Bugfix: user value replacement in the user registration mail.

= 2.6.3 =
* Array user value replacement in mails.
* Admin table list hover color.
* Pre-text and post-text for the widget.
* Treating the first line as the item names in importing users.
* User update and delete in importing users.

= 2.6.2 =
* Email confirmation (before registration).

= 2.6.1 =
* Cast for the user attributes.
* Soft user deletion from the admin user edit page.
* Bulk user update code option.

= 2.5.8 =
* Soft user deletion.

= 2.5.6 =
* fua.editUser for xml-rpc.
* Bugfix: xml-rpc acceptance.
* Bugfix: auto zipcode loading for ssl.
* Bugfix: user log url.

= 2.5.5 =
* Specification change of until more option and auto exerpt option.

= 2.5.4 =
* Current user value output shortcode.

= 2.5.3 =
* Bugfix: until more in the member condition.

= 2.5.2 =
* %redirect_to% replacement for the redirect url in the member condition.
* Clawler exception option for the member condition.
* Auto excerpt option for the member condition.
* Member condition shortcode.
* Placeholder for the user attributes.
* Bugfix: file upload with the smartphone.
* Bugfix: post list with the member condition.
* Bugfix: default avatar output in the discussion setting page.

= 2.5.1 =
* Bugifx: error output of the login page.

= 2.5 =
* Registration mail filters.
* Bugfix: unexpected transition in the user profile update.
* Bugifx: output of the login page.

= 2.4.9 =
* Alternative url for transfer all.
* Bugfix: file type required error.

= 2.4.8 =
* Hiragana and Katakana for the user attribute conditions.
* User attribute expanding for the user import.
* Bugfix: application to `attachment` by the member condition.

= 2.4.7 =
* Code cleaning.
* Bugfix: email confirmation user registration in the multi site.
* Bugfix: widget output.

= 2.4.6 =
* Bugfix: user registration through the FUA Social Login plugin in case not to use the option of `use the email as the username`.
* Bugfix: widget menu output without the user registration.

= 2.4.5 =
* Bugfix: wrong replacement of the user attribute `datetime`.

= 2.4.4 =
* Bugfix: member condition multiple setting.

= 2.4.3 =
* Bugfix: https.

= 2.4.2 =
* Member condition multiple setting.
* Default member condition.
* Bugifx: duplicate login setting expression.

= 2.4.1 =
* Bugfix: array save for the instant editor.
* Bugfix: https.

= 2.4 =
* Instant editor in the user edit.
* Clear button for the radio type in the admin user edit page.
* Admin options for default attributes in the profile item and order.
* Bugfix: unnecessary redirection by the member page condition.
* Bugfix: user update in the admin in the use of the option of email as user login and the fua social plugin.
* Bugfix: nickname as the display name.
* Bugfix: required check in the admin user edit page.

= 2.3.3 =
* Profile update hook.

= 2.3.2 =
* Backward compatibility.

= 2.3.1 =
* Email confirmation hook.

= 2.3 =
* Bugfix: filter loading order.

= 2.2.9 =
* Registration hook.

= 2.2.8 =
* Required field check.
* Support for the FUA Social Login plugin.

= 2.2.7 =
* Username Regular Expressions.

= 2.2.6 =
* Bugfix: email confirmation when an user get back to the confirmation screen after the user registration.

= 2.2.5 =
* Bugfix: datetime attribute.

= 2.2.4 =
* Bugfix: zero value edit.
* Bugfix: errors.

= 2.2.3 =
* User export: role, login datetime, update datetime, and registered datetime.
* Backward compatibility.
* Bugfix: user import.

= 2.2.2 =
* Bugfix: datetime user attribute output.

= 2.2.1 =
* Bugfix: Add User submit button label.
* Bugfix: registered date offset fix.
* Bugfix: datetime user attribute output.

= 2.2 =
* Bugfix: password auto regeneration.
* Bugfix: password in the profile update mail.

= 2.1.9 =
* Login check of approval registration.
* Change of treatment of the user status, no log, duplicate login.
* Admin JavaScript.
* Bugfix: duplicate login with the authentication timeout.
* Bugfix: output until the More separation.

= 2.1.8 =
* No output option for the member page condition.
* Bugfix: user attribute required option.

= 2.1.7 =
* File type output for smart phones.
* Active user search in the user list.
* Duplicate login message.
* Extension of XML RPC methods.
* PHP Code in the user update and PHP Code in the login.
* Bugfix: site top url of the `transfer all to the Log In Exception URL`.

= 2.1.6 =
* Bugfix: SSL judgement in some cases.

= 2.1.5 =
* Normal and "Remember me" authentication timeout.
* Bugfix: wishlist transfer of the net shop admin plugin.

= 2.1.4 =
* Separation of the email confirmation and approval wait in the user list.
* Approval and delete bulk actions.
* Password auto regeneration in sending a registration email without the password registration by users.
* Bugfix: display type output of the user registration.

= 2.1.3 =
* Option of conditional conjunction in the member page condition.
* Markin up the sign of the overwritting php code for user attributes.
* Bugfix: login error message in the multi site.

= 2.1.2 =
* Option to output until the More separation in the member page condition.
* Role search.
* Bugfix: array values in the member page condition.

= 2.1.1 =
* Bugfix: vulnerability of the admin panel operation.

= 2.1 =
* File type and avatar.
* Extension of transfer all to the log in exception url.
* Bugfix: after log in url through the login widget.

= 2.0.1 =
* Multiple values separated with a space in the member page condition.
* Bugfix: mobile and smartphone output with the breakpoint type.

= 2.0 =
* Member page condition.

= 1.9 =
* Smartphone options.
* Userlog and Usermail table speed up.
* Code cleaning.
* Bugfix: user mail count.
* Bugfix: user list page navigation in multi sites.

= 1.8.1 =
* Bugfix: inappropriate output in the email confirmation registration.

= 1.8 =
* Bugfix: hidden and breakpoint attributes in the admin screen.

= 1.7.9 =
* ID output in the user data export.

= 1.7.8 =
* Pending user search output in the user list screen.
* Bugfix: email confirmation registration.

= 1.7.7 =
* Bugfix: checkbox type.

= 1.7.6 =
* Bugfix: login after registration.
* Bugfix: default message in the confirmation screen.

= 1.7.5 =
* Change of the way to load widgets.

= 1.7.4 =
* Option to show the menu in the login page during login.
* Set up the common password automatically in registration.

= 1.7.3 =
* Bugfix: ajaxzip in SSL.
* Bugfix: email registration with unauthorized characters by default.

= 1.7.2 =
* Revision of the datetime attribute.
* Bugfix: Output option of the register code for mobile.

= 1.7.1 =
* Bugfix: Overwritting php code for user attributes.

= 1.7 =
* Support for WordPress 3.3.
* Username automatic generation.
* Breakpoint and hidden types for user attributes.
* Overwritting php code for user attributes.
* Mail functionality.
* Change of the default FROM format.
* Email duplication option.
* Bugfix: User withdrawal for multi sites.

= 1.6.3 =
* All site registration for multisites.
* User attribute conversion of password reset emails.
* Bugfix: user search in multi sites.

= 1.6.2 =
* Apply the OR search with a space in the search keyword in the user list.
* A different password from old one as a new password.
* Profile update with the check of `Transfer to the After Log In URL when the user logged in`.

= 1.6.1 =
* Bugfix: export of the user log summary.

= 1.6 =
* Bugfix: user list page navigation.

= 1.5.9 =
* Speed up the plugin.
* Bugfix: user list page navigation.

= 1.5.8 =
* Login after registration automatically.

= 1.5.7 =
* Profile update mail.
* Skip the action hook after the confirmation page.
* Bugfix: inadequate withdrawal message after the login miss.
* Bugfix: comment message of user attributes.

= 1.5.6 =
* Comparison search of the user list.
* Match partial and match full of the user log list.
* User log delete option.
* Bugfix: checkboxes which is affected by upgrade of jQuery from WordPress 3.2.

= 1.5.5 =
* Match partial and match full of the user list.
* Table list header image.
* Admin CSS.
* Support of Multi Site.
* Bugfix: user list column count.

= 1.5.4 =
* Support of the Wish List functionality.

= 1.5.3 =
* Email confirmation before the user registration.
* Bugfix: confirmation of the role before editing users.
* Bugfix: priority of the redirect url.
* Bugfix: priority of user_login in editing the user info in admin.
* Bugfix: History and Affiliate combination.

= 1.5.2 =
* Login url without the permalink setting.
* Bugfix: user data export select box.

= 1.5.1 =
* Bugfix: email update.
* Bugfix: user import.
* Bugfix: profile update without the email field.
* Bugfix: password expiration date.
* Bugfix: password on the email from the admin panel.

= 1.5 =
* Password expiration date.
* User log summary.
* Logout time.
* Login datetime and update datetime.
* Password Lock.
* Withdrawal mail and notice.
* Bugfix: user profile update.
* Bugfix: approval registration.
* Bugfix: display field type of the user attribute.

= 1.4.6 =
* Bugfix: output for old mobile phones.

= 1.4.5 =
* Bugfix: http error in the media upload.

= 1.4.4 =
* Bugfix: option to disable the admin bar.

= 1.4.3 =
* Bugfix: option to disable the admin bar.
* Bugfix: password strength.
* Bugfix: textarea value.

= 1.4.2 =
* Bugfix: checkboxes of the widhet menu.

= 1.4.1 =
* Option to disable the admin bar.
* Title option for my page.
* Encode option for user data export.
* Bugfix: user data and user log export limit.
* Bugfix: user list and user log pagination.

= 1.4 =
* Bugfix: Automatic slash to the login url.
* Bugfix: link after the user delete.
* Bugfix: plugin role change.
* Bugfix: total count sql.

= 1.3.9 =
* Bugfix: decode from ktai with checkbox attributes.

= 1.3.8 =
* Default user attribute required option.
* Bugfix: nickname in the admin add user page.
* Bugfix: password strength.
* Bugfix: required attribute check for the admin page.
* Bugfix: plugin role change.

= 1.3.7 =
* Plugin user role instead of the plugin user level.
* Bugfix: cleaning of codes.

= 1.3.6 =
* Bugfix: change of the save way.

= 1.3.5 =
* Affiliate for the Net Shop Admin plugin.
* Unique field for the user attribute option.
* Automatic slash to the login url.
* Bugfix: Role output on the user list.

= 1.3.4 =
* Bugfix: Output Options.

= 1.3.3 =
* Loading of the initial codes for Output Options.
* Mobile codes.

= 1.3.2 =
* Bugfix: user attribute update.

= 1.3.1 =
* User withdrawal.
* Bugfix: Output Options.

= 1.3 =
* Title Options.
* Output Options.
* After Log Out URL.
* An option to disable the widget while login.
* Delete User option.
* PHP Code in the user registration.
* Use of the widget as the shortcode.
* Bugfix: eval system.
* Bugfix: imported user count.

= 1.2.7 =
* Bugfix: order link on the user list.
* Bugfix: user registration link.

= 1.2.6 =
* User registration option.

= 1.2.5 =
* After Log In URL Exception URL.
* Bugfix: import option check.
* Bugfix: error display on the registration page.
* Bugfix: max and min letters of user attributes.

= 1.2.4 =
* Bugfix: user list and export.

= 1.2.3 =
* Bugfix: user attribute update.
* Bugfix: update redirect.

= 1.2.2 =
* User attributes for password retrieval.
* Bugfix: Transfer all to the Log In Exception URL.

= 1.2 =
* Mobile order with the net shop admin plugin.
* Resizable textarea.
* Bugfix: href in the user log page.
* Bugfix: user list search.
* Bugfix: replace the title.
* Bugfix: the judgement of user login in the buying history page.

= 1.1 =
* Works with the Ktai Style plugin.
* Added the plugin user level option.
* Added the approval process mail option.
* Added the admin demo mode.
* Added the Transfer all to the Log In Exception URL.
* Added the autocomplete off attribute into the password input fields of the profile page.
* Bugfix: the user login set when the email is used as the user login and is changed.
* Bugfix: %key% in the password reset email.
* Bugfix: user attribute checkboxes.

= 1.0.4 =
* Added the option of open widget menus.
* Added the option of the site owner approval.
* Added the notice option.

= 1.0.3 =
* Bugfix: admin panel user level.

= 1.0.2 =
* Mistranslation.

= 1.0.1 =
* Added Widget menu clasess.

= 1.0 =
* Initial release.

== Screenshots ==

1. Frontend User Admin - Settings

== Uninstall ==

1. Deactivate the plugin
2. That's it! :)
