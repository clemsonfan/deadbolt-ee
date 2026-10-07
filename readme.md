# Deadbolt for ExpressionEngine 7

<img src="icon.svg" alt="Deadbolt vault door icon" width="96" height="96">

Deadbolt protects template content with one of several named keys configured in the site's global `config.php`. It provides a form tag that posts to an ExpressionEngine action and a check tag pair that branches on the result.

## Requirements

- ExpressionEngine 7.2 or newer
- PHP 8.0 or newer

Deadbolt uses ExpressionEngine's add-on, action, session, and CAPTCHA services and has no third-party runtime dependencies. Composer is not required to install or use the add-on.

## Installation

1. Copy this add-on into the site's ExpressionEngine add-ons directory as `deadbolt` (commonly `system/user/addons/deadbolt`).
2. Add the named key map below to the site's active global `config.php`.
3. In the Control Panel, open **Developer → Add-Ons** and install Deadbolt.
4. Configure the desired image CAPTCHA or reCAPTCHA v3 settings in ExpressionEngine's **Settings → CAPTCHA** area.
5. Add a Deadbolt form and check tag to templates as shown below.

## Configure keys

Add a map to the site's active global `config.php`:

```php
$config['deadbolt_locks'] = [
    'front_door' => 'replace-with-a-long-random-secret',
    'staff_area' => 'use-a-different-long-random-secret',
];
```

Each array key is the lock name used by templates. Each value is the exact key accepted for that lock. Use distinct, high-entropy values, keep this file private, and do not put key values in templates. There is no default lock; a missing, empty, or unknown lock fails closed.

## Render the key form

```html
{exp:deadbolt:form lock="front_door" return="/members/thanks" class="access-form" id="access-form"}
    <label for="deadbolt-key">Key</label>
    <input id="deadbolt-key" type="password" name="deadbolt_key" autocomplete="off" required>
    {deadbolt_captcha}
    <button type="submit">Continue</button>
{/exp:deadbolt:form}
```

`lock` is required and selects the configured key. `return` is optional and defaults to the site's index; only a relative same-site path is accepted. `class` and `id` are optional and are rendered on the `<form>` element with HTML escaping. The tag renders the opening and closing form tags, posts to Deadbolt's registered ExpressionEngine action, and includes the EE CSRF token plus hidden lock and return fields. The template supplies the visible key input and submit control.

The `{deadbolt_captcha}` marker places native CAPTCHA output where it appears. Include it once. If omitted, the challenge is appended to the form when ExpressionEngine requires one.

## Check the submitted key

Put the check tag on the `return` destination template:

```html
{exp:deadbolt:check lock="front_door"}
    {if captcha_error}
        <p>CAPTCHA failed. Please try again.</p>
    {if:else}
        {if key_valid}
            <p>Key accepted.</p>
            <!-- Render content for the accepted key here. -->
        {if:else}
            <p>Key not accepted.</p>
        {/if}
    {/if}
{/exp:deadbolt:check}
```

The check tag requires the same `lock` as the form. `{if key_valid}` is true only when the submitted key matches and any required CAPTCHA succeeds. `{if captcha_error}` is true when required CAPTCHA validation fails. An invalid/missing key follows the `{if:else}` branch. A missing, expired, replayed, or mismatched result is invalid.

## CAPTCHA behavior

Deadbolt follows ExpressionEngine's native CAPTCHA preferences and visitor exemptions. It renders EE's challenge only when EE says one is required, and validates the submitted result through EE's CAPTCHA records. Built-in image CAPTCHA uses the native challenge and answer field. With reCAPTCHA v3 enabled, the form renders EE's native reCAPTCHA markup and relies on EE's native verification action. Configure reCAPTCHA credentials and the score threshold in ExpressionEngine; Deadbolt does not use a separate CAPTCHA key or Google verifier.

## Request and caching behavior

The form submits to Deadbolt and returns the visitor to the accepted same-site `return` path. The check tag displays the result of that submission for the current visitor, then removes the `deadbolt_result` parameter from the browser's current URL without reloading the page. The initial request may still appear in server access logs.

**ExpressionEngine template and tag caching are not supported** on any template containing `{exp:deadbolt:form}` or `{exp:deadbolt:check}`. Disable caching for those templates. Also exclude these pages from shared CDN or proxy caches so one visitor cannot receive another visitor's form or protected output.

## Troubleshooting

- **Every key is rejected:** check that the `lock` spelling matches an entry in `deadbolt_locks`, and that the submitted key exactly matches its configured string.
- **CAPTCHA error is always shown:** confirm CAPTCHA preferences and credentials in ExpressionEngine, that the challenge marker is inside the form, and that the visitor's IP is consistent across submission.
- **The result is invalid after redirect:** keep the form and return pages on the same site/session, ensure the result page includes the matching `{exp:deadbolt:check lock="…"}` tag, and avoid caching it.
- **The form action is missing:** confirm Deadbolt is installed in the Control Panel so its action is registered.
- **The form returns to the site index:** `return` was missing or rejected because it was not a safe relative same-site path.
