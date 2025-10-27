<div class="ck-admin-wrap">
    <div class="ck-admin-hero">
        <span class="ck-admin-hero__badge"><?php esc_html_e( 'Control Room', 'chatkit-wp' ); ?></span>
        <h1><?php esc_html_e( 'ChatKit Studio', 'chatkit-wp' ); ?></h1>
        <p><?php esc_html_e( 'Fine-tune how ChatKit appears on your site, invite visitors to email or chat, and verify your connection in one place.', 'chatkit-wp' ); ?></p>
    </div>

    <div class="ck-admin-layout">
        <aside class="ck-admin-sidebar">
            <nav class="ck-nav">
                <button type="button" class="ck-nav-link is-active" data-panel="basic">
                    <span class="ck-nav-title"><?php esc_html_e( 'Setup', 'chatkit-wp' ); ?></span>
                    <span class="ck-nav-caption"><?php esc_html_e( 'Keys & visibility rules', 'chatkit-wp' ); ?></span>
                </button>
                <button type="button" class="ck-nav-link" data-panel="appearance">
                    <span class="ck-nav-title"><?php esc_html_e( 'Appearance', 'chatkit-wp' ); ?></span>
                    <span class="ck-nav-caption"><?php esc_html_e( 'Button, layout, typography', 'chatkit-wp' ); ?></span>
                </button>
                <button type="button" class="ck-nav-link" data-panel="messages">
                    <span class="ck-nav-title"><?php esc_html_e( 'Conversation', 'chatkit-wp' ); ?></span>
                    <span class="ck-nav-caption"><?php esc_html_e( 'Greeting, prompts, disclaimers', 'chatkit-wp' ); ?></span>
                </button>
                <button type="button" class="ck-nav-link" data-panel="advanced">
                    <span class="ck-nav-title"><?php esc_html_e( 'Advanced', 'chatkit-wp' ); ?></span>
                    <span class="ck-nav-caption"><?php esc_html_e( 'Attachments & behaviours', 'chatkit-wp' ); ?></span>
                </button>
            </nav>
        </aside>

        <main class="ck-admin-main">
            <form method="post" action="">
                <?php wp_nonce_field( 'chatkit_settings_save' ); ?>

                <div class="ck-panel is-active" data-panel="basic">
                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Quick start', 'chatkit-wp' ); ?></h2>
                        <p class="ck-card__subtitle"><?php esc_html_e( 'Follow this checklist once per site. Everything else is optional.', 'chatkit-wp' ); ?></p>
                        <div class="ck-info-grid">
                            <div class="ck-info-box">
                                <strong>1. <?php esc_html_e( 'Create or select a Workflow', 'chatkit-wp' ); ?></strong>
                                <span><?php echo wp_kses_post( sprintf( __( 'Visit the %s and copy the ID that starts with <code>wf_</code>.', 'chatkit-wp' ), '<a href="https://platform.openai.com/agent-builder" target="_blank" rel="noopener">OpenAI Agent Builder</a>' ) ); ?></span>
                            </div>
                            <div class="ck-info-box">
                                <strong>2. <?php esc_html_e( 'Generate an API key', 'chatkit-wp' ); ?></strong>
                                <span><?php echo wp_kses_post( sprintf( __( 'Create a key inside the %s and keep it safe.', 'chatkit-wp' ), '<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI dashboard</a>' ) ); ?></span>
                            </div>
                            <div class="ck-info-box">
                                <strong>3. <?php esc_html_e( 'Allowlist your domain', 'chatkit-wp' ); ?></strong>
                                <span><?php echo wp_kses_post( sprintf( __( 'Add <code>%s</code> in the %s before enabling uploads.', 'chatkit-wp' ), esc_html( parse_url( home_url(), PHP_URL_HOST ) ), '<a href="https://platform.openai.com/settings/organization/chatkit" target="_blank" rel="noopener">ChatKit domain settings</a>' ) ); ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Credentials', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field-grid">
                            <div class="ck-field">
                                <label for="chatkit_api_key"><?php esc_html_e( 'OpenAI API key *', 'chatkit-wp' ); ?></label>
                                <input type="password" id="chatkit_api_key" name="chatkit_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="sk-proj-...">
                                <small><?php esc_html_e( 'Preferably store this in wp-config.php using CHATKIT_OPENAI_API_KEY.', 'chatkit-wp' ); ?></small>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_workflow_id"><?php esc_html_e( 'Workflow ID *', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_workflow_id" name="chatkit_workflow_id" value="<?php echo esc_attr( $workflow_id ); ?>" class="regular-text" placeholder="wf_..."></input>
                                <small><?php esc_html_e( 'Paste the workflow identifier from Agent Builder.', 'chatkit-wp' ); ?></small>
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Where should ChatKit appear?', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field-grid">
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_show_everywhere" <?php checked( $show_everywhere ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Display on every public page', 'chatkit-wp' ); ?></span>
                            </label>

                            <div class="ck-field">
                                <label for="chatkit_exclude_ids"><?php esc_html_e( 'Exclude specific content IDs', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_exclude_ids" name="chatkit_exclude_ids" value="<?php echo esc_attr( $exclude_ids ?? '' ); ?>" class="regular-text" placeholder="12, 34, 56">
                                <small><?php esc_html_e( 'Comma separated list of post, page, or CPT IDs.', 'chatkit-wp' ); ?></small>
                            </div>

                            <div class="ck-columns ck-columns--two">
                                <label class="ck-toggle">
                                    <input type="checkbox" name="chatkit_exclude_home" <?php checked( $exclude_home ?? false, true ); ?> />
                                    <span><?php esc_html_e( 'Hide on the front page', 'chatkit-wp' ); ?></span>
                                </label>
                                <label class="ck-toggle">
                                    <input type="checkbox" name="chatkit_exclude_archive" <?php checked( $exclude_archive ?? false, true ); ?> />
                                    <span><?php esc_html_e( 'Hide on archive templates', 'chatkit-wp' ); ?></span>
                                </label>
                                <label class="ck-toggle">
                                    <input type="checkbox" name="chatkit_exclude_search" <?php checked( $exclude_search ?? false, true ); ?> />
                                    <span><?php esc_html_e( 'Hide on search results', 'chatkit-wp' ); ?></span>
                                </label>
                                <label class="ck-toggle">
                                    <input type="checkbox" name="chatkit_exclude_404" <?php checked( $exclude_404 ?? false, true ); ?> />
                                    <span><?php esc_html_e( 'Hide on 404 pages', 'chatkit-wp' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Initial thread (optional)', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field">
                            <label for="chatkit_initial_thread_id"><?php esc_html_e( 'Thread ID to load first', 'chatkit-wp' ); ?></label>
                            <input type="text" id="chatkit_initial_thread_id" name="chatkit_initial_thread_id" value="<?php echo esc_attr( $initial_thread_id ?? '' ); ?>" class="regular-text" placeholder="thread_...">
                            <small><?php esc_html_e( 'Leave empty to start a fresh conversation each time.', 'chatkit-wp' ); ?></small>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Connection health', 'chatkit-wp' ); ?></h2>
                        <p class="ck-card__subtitle"><?php esc_html_e( 'Run this after changing keys or workflows to make sure everything is talking.', 'chatkit-wp' ); ?></p>
                        <button type="button" class="ck-button ck-button--ghost" data-chatkit-test>
                            🔍 <?php esc_html_e( 'Test API connection', 'chatkit-wp' ); ?>
                        </button>
                        <div class="ck-status" data-chatkit-test-result></div>
                    </section>
                </div>

                <div class="ck-panel" data-panel="appearance">
                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Floating button & palette', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field-grid">
                            <div class="ck-columns ck-columns--two">
                                <div class="ck-field">
                                    <label for="chatkit_button_text"><?php esc_html_e( 'Button label', 'chatkit-wp' ); ?></label>
                                    <input type="text" id="chatkit_button_text" name="chatkit_button_text" value="<?php echo esc_attr( $button_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Chat with us', 'chatkit-wp' ); ?>">
                                </div>
                                <div class="ck-field">
                                    <label for="chatkit_close_text"><?php esc_html_e( 'Close button text', 'chatkit-wp' ); ?></label>
                                    <input type="text" id="chatkit_close_text" name="chatkit_close_text" value="<?php echo esc_attr( $close_text ); ?>" class="regular-text" placeholder="✕">
                                </div>
                            </div>
                            <div class="ck-columns ck-columns--two">
                                <div class="ck-field">
                                    <label for="chatkit_accent_color"><?php esc_html_e( 'Accent colour', 'chatkit-wp' ); ?></label>
                                    <input type="color" id="chatkit_accent_color" name="chatkit_accent_color" value="<?php echo esc_attr( $accent_color ); ?>">
                                </div>
                                <div class="ck-field">
                                    <label for="chatkit_accent_level"><?php esc_html_e( 'Intensity', 'chatkit-wp' ); ?></label>
                                    <select id="chatkit_accent_level" name="chatkit_accent_level">
                                        <option value="0" <?php selected( $accent_level ?? '2', '0' ); ?>><?php esc_html_e( 'Subtle', 'chatkit-wp' ); ?></option>
                                        <option value="1" <?php selected( $accent_level ?? '2', '1' ); ?>><?php esc_html_e( 'Light', 'chatkit-wp' ); ?></option>
                                        <option value="2" <?php selected( $accent_level ?? '2', '2' ); ?>><?php esc_html_e( 'Balanced', 'chatkit-wp' ); ?></option>
                                        <option value="3" <?php selected( $accent_level ?? '2', '3' ); ?>><?php esc_html_e( 'Bold', 'chatkit-wp' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Placement & layout', 'chatkit-wp' ); ?></h2>
                        <div class="ck-columns ck-columns--two">
                            <div class="ck-field">
                                <label for="chatkit_button_size"><?php esc_html_e( 'Button size', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_button_size" name="chatkit_button_size">
                                    <option value="small" <?php selected( $button_size, 'small' ); ?>><?php esc_html_e( 'Small', 'chatkit-wp' ); ?></option>
                                    <option value="medium" <?php selected( $button_size, 'medium' ); ?>><?php esc_html_e( 'Medium', 'chatkit-wp' ); ?></option>
                                    <option value="large" <?php selected( $button_size, 'large' ); ?>><?php esc_html_e( 'Large', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_button_position"><?php esc_html_e( 'Docking corner', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_button_position" name="chatkit_button_position">
                                    <option value="bottom-right" <?php selected( $button_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom right', 'chatkit-wp' ); ?></option>
                                    <option value="bottom-left" <?php selected( $button_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom left', 'chatkit-wp' ); ?></option>
                                    <option value="top-right" <?php selected( $button_position, 'top-right' ); ?>><?php esc_html_e( 'Top right', 'chatkit-wp' ); ?></option>
                                    <option value="top-left" <?php selected( $button_position, 'top-left' ); ?>><?php esc_html_e( 'Top left', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_border_radius"><?php esc_html_e( 'Corner style', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_border_radius" name="chatkit_border_radius">
                                    <option value="square" <?php selected( $border_radius, 'square' ); ?>><?php esc_html_e( 'Squared', 'chatkit-wp' ); ?></option>
                                    <option value="round" <?php selected( $border_radius, 'round' ); ?>><?php esc_html_e( 'Rounded', 'chatkit-wp' ); ?></option>
                                    <option value="extra-round" <?php selected( $border_radius, 'extra-round' ); ?>><?php esc_html_e( 'Pill', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_shadow_style"><?php esc_html_e( 'Shadow depth', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_shadow_style" name="chatkit_shadow_style">
                                    <option value="subtle" <?php selected( $shadow_style, 'subtle' ); ?>><?php esc_html_e( 'Feathered', 'chatkit-wp' ); ?></option>
                                    <option value="normal" <?php selected( $shadow_style, 'normal' ); ?>><?php esc_html_e( 'Balanced', 'chatkit-wp' ); ?></option>
                                    <option value="bold" <?php selected( $shadow_style, 'bold' ); ?>><?php esc_html_e( 'Pronounced', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_density"><?php esc_html_e( 'Component density', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_density" name="chatkit_density">
                                    <option value="compact" <?php selected( $density, 'compact' ); ?>><?php esc_html_e( 'Compact', 'chatkit-wp' ); ?></option>
                                    <option value="normal" <?php selected( $density, 'normal' ); ?>><?php esc_html_e( 'Comfortable', 'chatkit-wp' ); ?></option>
                                    <option value="relaxed" <?php selected( $density, 'relaxed' ); ?>><?php esc_html_e( 'Relaxed', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_theme_mode"><?php esc_html_e( 'Theme', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_theme_mode" name="chatkit_theme_mode">
                                    <option value="dark" <?php selected( $theme_mode, 'dark' ); ?>><?php esc_html_e( 'Dark', 'chatkit-wp' ); ?></option>
                                    <option value="light" <?php selected( $theme_mode, 'light' ); ?>><?php esc_html_e( 'Light', 'chatkit-wp' ); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="ck-field">
                            <label for="chatkit_locale"><?php esc_html_e( 'Locale override', 'chatkit-wp' ); ?></label>
                            <input type="text" id="chatkit_locale" name="chatkit_locale" value="<?php echo esc_attr( $locale ?? '' ); ?>" class="regular-text" placeholder="en-US">
                            <small><?php esc_html_e( 'Leave empty to follow the site language.', 'chatkit-wp' ); ?></small>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Typography & header', 'chatkit-wp' ); ?></h2>
                        <div class="ck-columns ck-columns--two">
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_enable_custom_font" <?php checked( $enable_custom_font ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Use custom font family', 'chatkit-wp' ); ?></span>
                            </label>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_show_history" <?php checked( $show_history ?? true, true ); ?> />
                                <span><?php esc_html_e( 'Display chat history', 'chatkit-wp' ); ?></span>
                            </label>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_show_header" <?php checked( $show_header ?? true, true ); ?> />
                                <span><?php esc_html_e( 'Render header toolbar', 'chatkit-wp' ); ?></span>
                            </label>
                        </div>

                        <div class="ck-columns ck-columns--two">
                            <div class="ck-field">
                                <label for="chatkit_font_family"><?php esc_html_e( 'Font family', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_font_family" name="chatkit_font_family" value="<?php echo esc_attr( $font_family ?? '' ); ?>" class="regular-text" placeholder="'Inter', sans-serif">
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_font_size"><?php esc_html_e( 'Base size (px)', 'chatkit-wp' ); ?></label>
                                <input type="number" id="chatkit_font_size" name="chatkit_font_size" value="<?php echo esc_attr( (int) ( $font_size ?? 16 ) ); ?>" min="12" max="24">
                            </div>
                        </div>

                        <div class="ck-columns ck-columns--two">
                            <div class="ck-field">
                                <label for="chatkit_header_title_text"><?php esc_html_e( 'Header title', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_header_title_text" name="chatkit_header_title_text" value="<?php echo esc_attr( $header_title_text ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Need a hand?', 'chatkit-wp' ); ?>">
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_header_left_icon"><?php esc_html_e( 'Left action', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_header_left_icon" name="chatkit_header_left_icon">
                                    <option value=""><?php esc_html_e( 'None', 'chatkit-wp' ); ?></option>
                                    <option value="menu" <?php selected( $header_left_icon ?? '', 'menu' ); ?>><?php esc_html_e( 'Menu', 'chatkit-wp' ); ?></option>
                                    <option value="settings-cog" <?php selected( $header_left_icon ?? '', 'settings-cog' ); ?>><?php esc_html_e( 'Settings', 'chatkit-wp' ); ?></option>
                                    <option value="home" <?php selected( $header_left_icon ?? '', 'home' ); ?>><?php esc_html_e( 'Home', 'chatkit-wp' ); ?></option>
                                </select>
                                <input type="url" name="chatkit_header_left_url" value="<?php echo esc_attr( $header_left_url ?? '' ); ?>" class="regular-text" placeholder="https://example.com">
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_header_right_icon"><?php esc_html_e( 'Right action', 'chatkit-wp' ); ?></label>
                                <select id="chatkit_header_right_icon" name="chatkit_header_right_icon">
                                    <option value=""><?php esc_html_e( 'None', 'chatkit-wp' ); ?></option>
                                    <option value="home" <?php selected( $header_right_icon ?? '', 'home' ); ?>><?php esc_html_e( 'Home', 'chatkit-wp' ); ?></option>
                                    <option value="settings-cog" <?php selected( $header_right_icon ?? '', 'settings-cog' ); ?>><?php esc_html_e( 'Settings', 'chatkit-wp' ); ?></option>
                                    <option value="menu" <?php selected( $header_right_icon ?? '', 'menu' ); ?>><?php esc_html_e( 'Menu', 'chatkit-wp' ); ?></option>
                                </select>
                                <input type="url" name="chatkit_header_right_url" value="<?php echo esc_attr( $header_right_url ?? '' ); ?>" class="regular-text" placeholder="https://example.com">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="ck-panel" data-panel="messages">
                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Conversation defaults', 'chatkit-wp' ); ?></h2>
                        <div class="ck-columns ck-columns--two">
                            <div class="ck-field">
                                <label for="chatkit_greeting_text"><?php esc_html_e( 'Welcome message', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_greeting_text" name="chatkit_greeting_text" value="<?php echo esc_attr( $greeting_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'How can I help today?', 'chatkit-wp' ); ?>">
                            </div>
                            <div class="ck-field">
                                <label for="chatkit_placeholder_text"><?php esc_html_e( 'Composer placeholder', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_placeholder_text" name="chatkit_placeholder_text" value="<?php echo esc_attr( $placeholder_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Send a message…', 'chatkit-wp' ); ?>">
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Quick prompts', 'chatkit-wp' ); ?></h2>
                        <p class="ck-card__subtitle"><?php esc_html_e( 'Add up to five tap-to-ask suggestions.', 'chatkit-wp' ); ?></p>
                        <div class="ck-prompts">
                            <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                <div class="ck-prompt">
                                    <header>
                                        <h4><?php printf( esc_html__( 'Prompt %d', 'chatkit-wp' ), $i ); ?></h4>
                                        <small><?php esc_html_e( 'Optional', 'chatkit-wp' ); ?></small>
                                    </header>
                                    <div class="ck-field-grid">
                                        <div class="ck-field">
                                            <label for="chatkit_default_prompt_<?php echo $i; ?>"><?php esc_html_e( 'Button label', 'chatkit-wp' ); ?></label>
                                            <input type="text" id="chatkit_default_prompt_<?php echo $i; ?>" name="chatkit_default_prompt_<?php echo $i; ?>" value="<?php echo esc_attr( ${'default_prompt_' . $i} ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Ask about pricing', 'chatkit-wp' ); ?>">
                                        </div>
                                        <div class="ck-field">
                                            <label for="chatkit_default_prompt_<?php echo $i; ?>_text"><?php esc_html_e( 'Message sent to ChatKit', 'chatkit-wp' ); ?></label>
                                            <input type="text" id="chatkit_default_prompt_<?php echo $i; ?>_text" name="chatkit_default_prompt_<?php echo $i; ?>_text" value="<?php echo esc_attr( ${'default_prompt_' . $i . '_text'} ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Tell me about pricing options', 'chatkit-wp' ); ?>">
                                        </div>
                                        <div class="ck-field">
                                            <label for="chatkit_default_prompt_<?php echo $i; ?>_icon"><?php esc_html_e( 'Icon', 'chatkit-wp' ); ?></label>
                                            <select id="chatkit_default_prompt_<?php echo $i; ?>_icon" name="chatkit_default_prompt_<?php echo $i; ?>_icon">
                                                <option value="circle-question" <?php selected( ${'default_prompt_' . $i . '_icon'} ?? 'circle-question', 'circle-question' ); ?>><?php esc_html_e( 'Question mark', 'chatkit-wp' ); ?></option>
                                                <option value="search" <?php selected( ${'default_prompt_' . $i . '_icon'} ?? 'circle-question', 'search' ); ?>><?php esc_html_e( 'Search', 'chatkit-wp' ); ?></option>
                                                <option value="write" <?php selected( ${'default_prompt_' . $i . '_icon'} ?? 'circle-question', 'write' ); ?>><?php esc_html_e( 'Write', 'chatkit-wp' ); ?></option>
                                                <option value="home" <?php selected( ${'default_prompt_' . $i . '_icon'} ?? 'circle-question', 'home' ); ?>><?php esc_html_e( 'Home', 'chatkit-wp' ); ?></option>
                                                <option value="info" <?php selected( ${'default_prompt_' . $i . '_icon'} ?? 'circle-question', 'info' ); ?>><?php esc_html_e( 'Info', 'chatkit-wp' ); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Footer disclaimer', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field">
                            <label for="chatkit_disclaimer_text"><?php esc_html_e( 'Message shown below the composer', 'chatkit-wp' ); ?></label>
                            <textarea id="chatkit_disclaimer_text" name="chatkit_disclaimer_text" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'AI can make mistakes. Check important details.', 'chatkit-wp' ); ?>"><?php echo esc_textarea( $disclaimer_text ?? '' ); ?></textarea>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_disclaimer_high_contrast" <?php checked( $disclaimer_high_contrast ?? false, true ); ?> />
                                <span><?php esc_html_e( 'High contrast style', 'chatkit-wp' ); ?></span>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="ck-panel" data-panel="advanced">
                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Attachments', 'chatkit-wp' ); ?></h2>
                        <p class="ck-card__subtitle"><?php esc_html_e( 'Requires domain verification inside the OpenAI dashboard.', 'chatkit-wp' ); ?></p>
                        <div class="ck-field-grid">
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_enable_attachments" <?php checked( $enable_attachments ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Allow visitors to upload files', 'chatkit-wp' ); ?></span>
                            </label>
                            <div class="ck-input-inline">
                                <label for="chatkit_attachment_max_size"><?php esc_html_e( 'Max file size (MB)', 'chatkit-wp' ); ?></label>
                                <input type="number" id="chatkit_attachment_max_size" name="chatkit_attachment_max_size" min="1" max="100" value="<?php echo esc_attr( $attachment_max_size ?? '20' ); ?>">
                                <label for="chatkit_attachment_max_count"><?php esc_html_e( 'Max files per message', 'chatkit-wp' ); ?></label>
                                <input type="number" id="chatkit_attachment_max_count" name="chatkit_attachment_max_count" min="1" max="10" value="<?php echo esc_attr( $attachment_max_count ?? '3' ); ?>">
                            </div>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Session behaviour', 'chatkit-wp' ); ?></h2>
                        <div class="ck-field-grid">
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_persistent_sessions" <?php checked( $persistent_sessions ?? true, true ); ?> />
                                <span><?php esc_html_e( 'Remember visitors between visits (cookies)', 'chatkit-wp' ); ?></span>
                            </label>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_enable_model_picker" <?php checked( $enable_model_picker ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Expose model picker inside ChatKit', 'chatkit-wp' ); ?></span>
                            </label>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_enable_tools" <?php checked( $enable_tools ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Enable tool integrations', 'chatkit-wp' ); ?></span>
                            </label>
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_enable_entity_tags" <?php checked( $enable_entity_tags ?? false, true ); ?> />
                                <span><?php esc_html_e( 'Show entity tags in transcripts', 'chatkit-wp' ); ?></span>
                            </label>
                        </div>
                    </section>

                    <section class="ck-card">
                        <h2 class="ck-card__title"><?php esc_html_e( 'Attention nudge', 'chatkit-wp' ); ?></h2>
                        <p class="ck-card__subtitle"><?php esc_html_e( 'Show a gentle prompt after the button sits idle for a while.', 'chatkit-wp' ); ?></p>
                        <div class="ck-field-grid">
                            <label class="ck-toggle">
                                <input type="checkbox" name="chatkit_nudge_enabled" <?php checked( $nudge_enabled ?? true, true ); ?> />
                                <span><?php esc_html_e( 'Display the nudge message automatically', 'chatkit-wp' ); ?></span>
                            </label>
                            <div class="ck-field">
                                <label for="chatkit_nudge_message"><?php esc_html_e( 'Message text', 'chatkit-wp' ); ?></label>
                                <input type="text" id="chatkit_nudge_message" name="chatkit_nudge_message" value="<?php echo esc_attr( $nudge_message ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Need a hand? Tap to chat or send us a quick email.', 'chatkit-wp' ); ?>">
                            </div>
                            <div class="ck-input-inline">
                                <label for="chatkit_nudge_initial_delay"><?php esc_html_e( 'Initial delay (seconds)', 'chatkit-wp' ); ?></label>
                                <input type="number" id="chatkit_nudge_initial_delay" name="chatkit_nudge_initial_delay" min="3" max="300" value="<?php echo esc_attr( (int) ( $nudge_initial_delay ?? 12 ) ); ?>">
                                <label for="chatkit_nudge_repeat_delay"><?php esc_html_e( 'Repeat delay (seconds)', 'chatkit-wp' ); ?></label>
                                <input type="number" id="chatkit_nudge_repeat_delay" name="chatkit_nudge_repeat_delay" min="10" max="600" value="<?php echo esc_attr( (int) ( $nudge_repeat_delay ?? 36 ) ); ?>">
                            </div>
                        </div>
                    </section>
                </div>

                <div class="ck-admin-actions">
                    <button type="submit" class="ck-button ck-button--primary" name="chatkit_save_settings">
                        💾 <?php esc_html_e( 'Save changes', 'chatkit-wp' ); ?>
                    </button>
                    <span class="ck-save-note"><?php esc_html_e( 'Changes apply instantly to the widget once saved.', 'chatkit-wp' ); ?></span>
                </div>
            </form>
        </main>
    </div>
</div>
