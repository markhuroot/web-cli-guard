<?php
/**
 * Plugin Name: Web CLI Guard
 * Description: Example WordPress wrapper for a restricted tmux-backed CLI console.
 * Version: 0.1.0
 * Author: Example Maintainer
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class WebCliGuardPlugin
{
    private const SHORTCODE = 'web_cli_guard_console';
    private const CAPABILITY = 'manage_options';

    public static function init(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render_console_shortcode']);
    }

    public static function render_console_shortcode(): string
    {
        if (!current_user_can(self::CAPABILITY)) {
            return '<div class="wcg-console-denied">You do not have access to this console.</div>';
        }

        ob_start();
        ?>
        <div class="wcg-console">
            <style>
                .wcg-console { max-width: 1160px; margin: 24px auto; padding: 24px; border: 1px solid #d7dee8; border-radius: 24px; background: #fff; box-shadow: 0 16px 36px rgba(15,23,42,.06); color: #17212f; }
                .wcg-console h2 { margin: 0 0 8px; font-size: 28px; }
                .wcg-console p { color: #516072; line-height: 1.7; }
                .wcg-grid { display: grid; grid-template-columns: 280px minmax(0,1fr); gap: 16px; margin-top: 18px; }
                .wcg-card { border: 1px solid #d7dee8; border-radius: 20px; background: #fbfdff; padding: 16px; }
                .wcg-card h3 { margin: 0 0 12px; font-size: 16px; }
                .wcg-session { display: grid; gap: 10px; }
                .wcg-session button { min-height: 40px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; }
                .wcg-screen { min-height: 340px; border-radius: 18px; padding: 16px; background: #0b1220; color: #d7e3f4; font: 13px/1.55 Consolas, Monaco, monospace; white-space: pre-wrap; }
                .wcg-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
                .wcg-actions button { min-height: 38px; padding: 0 14px; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; }
                .wcg-note { margin-top: 16px; padding: 14px 16px; border-radius: 16px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
                @media (max-width: 860px) { .wcg-grid { grid-template-columns: 1fr; } }
            </style>
            <h2>Web CLI Guard</h2>
            <p>This is a clean public plugin scaffold for a tmux-backed web CLI console. Wire this UI to your own narrow bridge and low-privilege runtime user.</p>
            <div class="wcg-grid">
                <div class="wcg-card">
                    <h3>Sessions</h3>
                    <div class="wcg-session">
                        <button type="button">agent-main</button>
                        <button type="button">repo-main</button>
                    </div>
                </div>
                <div class="wcg-card">
                    <h3>Console</h3>
                    <div class="wcg-screen">Readonly output area placeholder.

This public starter does not execute commands yet.
Connect it to your own bridge, audit layer, and verification flow.</div>
                    <div class="wcg-actions">
                        <button type="button">Enter</button>
                        <button type="button">Ctrl+C</button>
                        <button type="button">Refresh</button>
                    </div>
                    <div class="wcg-note">
                        Recommended model: run the CLI under a dedicated OS user, keep tmux sessions on an allowlist, and require OTP or approval for elevated commands.
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

WebCliGuardPlugin::init();
