# FriendsOfFlarum Ignore Users

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/ignore-users.svg)](https://packagist.org/packages/fof/ignore-users)

A [Flarum](http://flarum.org) extension. Ignore users - hiding their posts in discussions and stopping them from sending you direct messages, in both [Flarum Messages](https://github.com/flarum/framework/tree/2.x/extensions/messages) and [FoF Byobu](https://github.com/FriendsOfFlarum/byobu).

## Installation

Install manually with composer:

```sh
composer require fof/ignore-users:"*"
```

## Updating

```sh
composer update fof/ignore-users
```

## Usage

### Ignoring a user

Open a user's profile, then choose **Ignore** from the user controls (the dropdown next to their name). Choose **Unignore** from the same menu to reverse it. Ignoring is private: the other user is not told.

While you are ignoring someone:

- Their posts are collapsed in discussions. Use the **...** button in the post header to reveal one.
- An **Ignored user** badge appears on their profile, and discussions they started show a **Started by an ignored user** badge.
- They can no longer start or continue a direct message with you (see below).

Everyone you ignore is listed under **Ignored Users** on your own profile, where you can unignore them.

Ignoring is one-directional. If you ignore someone, you can still message them.

### Direct messages

The extension integrates with both direct messaging extensions. Each integration only applies when that extension is installed and enabled; neither is required.

| Extension | What is blocked |
| --- | --- |
| [`flarum/messages`](https://docs.flarum.org/extensions/messages) | Starting a new conversation with someone who ignores you, and sending further messages in an existing conversation with them. |
| [`fof/byobu`](https://github.com/FriendsOfFlarum/byobu) | Starting a private discussion that includes someone who ignores you, and adding them to an existing private discussion. |

The blocked user receives a "permission denied" error. In Byobu, only newly added recipients are checked, so private discussions that already include someone who later ignores a participant are left alone.

Both integrations use a single permission check, `sendDirectMessage`, so other extensions can ask the same question with `$actor->can('sendDirectMessage', $recipient)`.

### Users who cannot be ignored

In the admin dashboard, under **Permissions**, grant **Can not be ignored** to any group that should always be reachable, such as moderators or staff. Members of those groups:

- do not show an **Ignore** option on their profile, and
- can still send direct messages to users who have ignored them.

Administrators are always exempt.

## Development

Tests use the Flarum testing harness. Run them from this directory with the composer scripts:

```sh
composer test:setup        # one-off: installs the integration test database
composer test:unit
composer test:integration
composer test              # unit + integration
composer analyse:phpstan
```

The messages and byobu integration tests need `flarum/messages`, `fof/byobu` and `flarum/tags` installed, which `composer install` provides through `require-dev`.

## Links

- [Packagist](https://packagist.org/packages/fof/ignore-users)
- [GitHub](https://github.com/FriendsOfFlarum/ignore-users)

An extension by [FriendsOfFlarum](https://github.com/FriendsOfFlarum), commissioned by [giffgaff](https://community.giffgaff.com).
