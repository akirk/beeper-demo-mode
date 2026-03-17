# Beeper Demo Mode

A WordPress plugin that anonymizes names and images from the Beeper client used by the [keeping-contact](../keeping-contact) and [chat-to-blog](../chat-to-blog) plugins, making it safe to take screenshots and record demos without exposing real contact data.

## Features

- Replaces real contact names with randomly assigned fictional names
- Replaces contact images with a placeholder
- Per-name overrides: edit, randomize, or reveal individual names from the settings page
- Image whitelist: selectively reveal specific images while keeping others hidden
- Deterministic name assignment (same real name always maps to the same fake name)
- Toggle via **Settings → Beeper Demo Mode** or programmatically via a WordPress filter

## Usage

Enable demo mode from the WordPress admin under **Settings → Beeper Demo Mode**, or activate it directly in code:

```php
add_filter( 'beeper_demo_mode', '__return_true' );
```

## Filters

### `beeper_demo_mode`

Controls whether demo mode is active. Return `true` to enable.

```php
add_filter( 'beeper_demo_mode', '__return_true' );
```

### `beeper_demo_names`

Override the fake name lists used for anonymization.

```php
add_filter( 'beeper_demo_names', function( $names ) {
    $names['first'] = [ 'Jean', 'Pierre', 'Marie' ];
    $names['last']  = [ 'Dupont', 'Martin', 'Bernard' ];
    return $names;
} );
```

### `beeper_demo_placeholder_image`

Override the placeholder image URL used in place of contact photos.

```php
add_filter( 'beeper_demo_placeholder_image', function( $url ) {
    return 'https://example.com/my-placeholder.png';
} );
```

## Settings Page

The settings page (**Settings → Beeper Demo Mode**) provides:

- **Demo Mode toggle** — enable/disable name and image anonymization
- **Image whitelist** — browse images seen in the current browser session, load them to preview, and whitelist specific ones to display normally
- **Name overrides** — view all names seen in demo mode, edit their fake replacements, randomize to a different fake name, or revert to the real name
