<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use AesirxAnalytics\CliFactory;

add_action('admin_init', function () {
  register_setting('aesirx_analytics_plugin_options', 'aesirx_analytics_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    if ($input['storage'] == 'internal') {
      if (empty($input['license'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'license',
          esc_html__('License is empty.', 'aesirx-analytics')
        );
      }
    } elseif ($input['storage'] == 'external') {
      if (empty($input['domain'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Domain is empty.', 'aesirx-analytics')
        );
      } elseif (filter_var($input['domain'], FILTER_VALIDATE_URL) === false) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Invalid domain format.', 'aesirx-analytics')
        );
      }
    }

    // Ignore the user's changes and use the old database value.
    if (!$valid) {
      $value = get_option('aesirx_analytics_plugin_options');
    }

    return $value;
  });
  add_settings_section(
    'aesirx_analytics_settings',
    'Aesirx Analytics',
    function () {
      echo wp_kses_post(
        /* translators: %s: URL to aesir.io read mor details */
        sprintf('<p>'. esc_html__('Read more detail at', 'aesirx-analytics') .' <a target="_blank" href="%s">%s</a></p><p class= "description">
        <p>'. esc_html__('Note: Please set Permalink structure is NOT plain.', 'aesirx-analytics') .'</p></p>', 'https://github.com/aesirxio/analytics#in-ssr-site', 'https://github.com/aesirxio/analytics#in-ssr-site')
      );
    },
    'aesirx_analytics_plugin'
  );

  add_settings_field(
    'aesirx_analytics_storage',
    esc_html__('1st party server', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['storage'] ?? 'internal';
      echo aesirx_analytics_escape_html('
    <label>' . esc_html__('Internal', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'internal' ? $checked : '') .
        ' value="internal"  /></label>
    <label>' . esc_html__('External', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'external' ? $checked : '') .
        ' value="external" /></label>');

        echo '<script>
        jQuery(document).ready(function() {
      function switch_radio(test) {
        var donwload = jQuery("#aesirx_analytics_download");
        if (test === "internal") {
          jQuery("#aesirx_analytics_domain").parents("tr").hide();
          jQuery("#aesirx_analytics_clientid").parents("tr").hide();
          jQuery("#aesirx_analytics_secret").parents("tr").hide();
          jQuery("#aesirx_analytics_license").parents("tr").show();
          donwload.parents("tr").show();
        } else {
          jQuery("#aesirx_analytics_domain").parents("tr").show();
          jQuery("#aesirx_analytics_license").parents("tr").hide();
          jQuery("#aesirx_analytics_clientid").parents("tr").show();
          jQuery("#aesirx_analytics_secret").parents("tr").show();
          donwload.parents("tr").hide();
        }
      }
        jQuery("input.analytic-storage-class").click(function() {
        switch_radio(jQuery(this).val())
        });
      switch_radio("' .
            $storage .
            '");
    });
    </script>';

      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, null, true);
        }
      }
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_domain',
    __('domain <i>(Use next format: http://example.com:1000/)</i>', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_domain' name='aesirx_analytics_plugin_options[domain]' type='text' value='" .
        esc_attr($options['domain'] ?? '') .
        "' />"
           /* translators: %s: URL to aesir.io */
           /* translators: %s: URL to aesir.io */
           . sprintf(__("<p class= 'description'>
		    You can setup 1st party server at <a target='_blank' href='%s'>%s</a>.</p>", 'aesirx-analytics'), 'https://github.com/aesirxio/analytics-1stparty', 'https://github.com/aesirxio/analytics-1stparty')
      );
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  if (!CliFactory::getCli()->analyticsCliExists()) {
    add_settings_field(
        'aesirx_analytics_download',
        esc_html__( 'Download', 'aesirx-analytics' ),
        function () {
          try {
              CliFactory::getCli()->getSupportedArch();

            echo aesirx_analytics_escape_html('<button name="submit" id="aesirx_analytics_download" class="button button-primary" type="submit" value="download_analytics_cli">' . esc_html__(
                    'Click to download CLI library! This plugin can\'t work without the library!', 'aesirx-analytics'
                ) . '</button>');
          }
          catch ( Throwable $e ) {
            echo wp_kses_post('<strong style="color: red">' . sprintf(esc_html__( 'You can\'t use internal server. Error: %s', 'aesirx-analytics' ) , $e->getMessage()) . '</strong>');
          }
        },
        'aesirx_analytics_plugin',
        'aesirx_analytics_settings'
    );
  } else {
      add_settings_field(
          'aesirx_analytics_download',
          __( 'CLI library check', 'aesirx-analytics' ),
          function () {
              try {
                  CliFactory::getCli()->processAnalytics(['--version']);
				  echo wp_kses_post('<strong style="color: green" id="aesirx_analytics_download">' . esc_html__( 'Passed', 'aesirx-analytics' ) . '</strong>');
              } catch (Throwable $e) {
                  echo wp_kses_post('<strong style="color: red" id="aesirx_analytics_download">' . sprintf(esc_html__( 'You can\'t use internal server. Error: $s', 'aesirx-analytics' ), $e->getMessage()) . '</strong>');
			  }
          },
          'aesirx_analytics_plugin',
          'aesirx_analytics_settings'
      );
  }

  add_settings_field(
    'aesirx_analytics_consent',
    __('Consent', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['consent'] ?? 'true';
      echo aesirx_analytics_escape_html('
        <label>' . esc_html__('Yes', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'true' ? $checked : '') .
            ' value="true"  /></label>
        <label>' . esc_html__('No', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'false' ? $checked : '') .
            ' value="false" /></label>');
    }, 
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_clientid',
    esc_html__('Client ID', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_clientid' name='aesirx_analytics_plugin_options[clientid]' type='text' value='" .
        esc_attr($options['clientid'] ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_secret',
    esc_html__('Client secret', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
        esc_attr($options['secret'] ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_license',
    esc_html__('License', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
        esc_attr($options['license'] ?? '') .
        "' /> <p class= 'description'>
        Register to AesirX and get your client id, client secret and license here: <a target='_blank' href='https://web3id.aesirx.io'>https://web3id.aesirx.io</a>.</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_track_ecommerce',
    esc_html__('Track ecommerce', 'aesirx-analytics'),
    function () {

        $options = get_option('aesirx_analytics_plugin_options', []);
        $checked = 'checked="checked"';
        $storage = $options['track_ecommerce'] ?? 'true';
        echo aesirx_analytics_escape_html('
        <label>' . esc_html__('Yes', 'aesirx-analytics') . ' <input type="radio" class="analytic-track_ecommerce-class" name="aesirx_analytics_plugin_options[track_ecommerce]" ' .
             ($storage == 'true' ? $checked : '') .
             ' value="true"  /></label>
        <label>' . esc_html__('No', 'aesirx-analytics') . ' <input type="radio" class="analytic-track_ecommerce-class" name="aesirx_analytics_plugin_options[track_ecommerce]" ' .
             ($storage == 'false' ? $checked : '') .
             ' value="false" /></label>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_analytics_info',
    '',
    function () {
      echo aesirx_analytics_escape_html('<div class="aesirx_analytics_info"><div class="wrap">Sign up for a
      <h3>FREE License</h3><p>at the AesirX Shield of Privacy dApp</p><div>
      <a target="_blank" href="https://dapp.shield.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">Get Free License</a></div>');
    },
    'aesirx_analytics_info'
  );

});

add_action('admin_menu', function () {
  add_options_page(
    esc_html__('Aesirx Analytics', 'aesirx-analytics'),
    esc_html__('Aesirx Analytics', 'aesirx-analytics'),
    'manage_options',
    'aesirx-analytics-plugin',
    function () {
      ?>
			<form action="options.php" method="post">
				<?php
    settings_fields('aesirx_analytics_plugin_options');
    do_settings_sections('aesirx_analytics_plugin');
    ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e(
      'Save', 'aesirx-analytics'
    ); ?>"/>
			</form>
			<?php
      do_settings_sections('aesirx_analytics_info');
    }
  );

  add_menu_page(
    'AesirX BI Dashboard',
    'AesirX BI',
    'manage_options',
    'aesirx-bi-dashboard',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTcuNTY5ODIgOS4xNjg0NkM4LjY4NzIxIDkuMDk4NTMgOS4xOTYyNiA4LjcyMDM5IDkuMTk2MjYgNy45NjQwOEM5LjE5NjI2IDcuMzk3MjUgOC40NTEzNCA3LjExMjYyIDcuNTY5ODIgNi44Njg2NVY5LjE2ODQ2WiIgZmlsbD0iIzlDQTJBNyIvPgo8Y2lyY2xlIGN4PSI5IiBjeT0iOSIgcj0iOSIgZmlsbD0id2hpdGUiLz4KPG1hc2sgaWQ9InBhdGgtMy1vdXRzaWRlLTFfMTk1MzZfNTY3OTMiIG1hc2tVbml0cz0idXNlclNwYWNlT25Vc2UiIHg9IjUuMTI3OTMiIHk9IjMuMDUxNzYiIHdpZHRoPSI3IiBoZWlnaHQ9IjExIiBmaWxsPSJibGFjayI+CjxyZWN0IGZpbGw9IndoaXRlIiB4PSI1LjEyNzkzIiB5PSIzLjA1MTc2IiB3aWR0aD0iNyIgaGVpZ2h0PSIxMSIvPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTguMzUwMTcgOC42ODc0VjEzLjM4MTZIOS4xNzYxM1Y5LjU3MjhMMTEuMDU3OSAxMS41OUwxMS42NTI4IDEwLjk5ODZMOS4xNzYxMyA4LjM0MzYzVjQuMDUxNzZIOC4zNTAxN1Y3LjQ1ODIzTDYuNzIyODkgNS43MTM4Mkw2LjEyNzkzIDYuMzA1MjFMOC4zNTAxNyA4LjY4NzRaIi8+CjwvbWFzaz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik04LjM1MDE3IDguNjg3NFYxMy4zODE2SDkuMTc2MTNWOS41NzI4TDExLjA1NzkgMTEuNTlMMTEuNjUyOCAxMC45OTg2TDkuMTc2MTMgOC4zNDM2M1Y0LjA1MTc2SDguMzUwMTdWNy40NTgyM0w2LjcyMjg5IDUuNzEzODJMNi4xMjc5MyA2LjMwNTIxTDguMzUwMTcgOC42ODc0WiIgZmlsbD0iIzJDMzMzOCIvPgo8cGF0aCBkPSJNOC4zNTAxNyAxMy4zODE2SDguMTUwMTdWMTMuNTgxNkg4LjM1MDE3VjEzLjM4MTZaTTguMzUwMTcgOC42ODc0SDguNTUwMTdWOC42MDg2TDguNDk2NDIgOC41NTA5N0w4LjM1MDE3IDguNjg3NFpNOS4xNzYxMyAxMy4zODE2VjEzLjU4MTZIOS4zNzYxM1YxMy4zODE2SDkuMTc2MTNaTTkuMTc2MTMgOS41NzI4TDkuMzIyMzggOS40MzYzOEw4Ljk3NjEzIDkuMDY1MjFWOS41NzI4SDkuMTc2MTNaTTExLjA1NzkgMTEuNTlMMTAuOTExNiAxMS43MjY0TDExLjA1MjQgMTEuODc3NEwxMS4xOTg5IDExLjczMThMMTEuMDU3OSAxMS41OVpNMTEuNjUyOCAxMC45OTg2TDExLjc5MzggMTEuMTQwNEwxMS45MzEyIDExLjAwMzhMMTEuNzk5MSAxMC44NjIxTDExLjY1MjggMTAuOTk4NlpNOS4xNzYxMyA4LjM0MzYzSDguOTc2MTNWOC40MjI0M0w5LjAyOTg5IDguNDgwMDZMOS4xNzYxMyA4LjM0MzYzWk05LjE3NjEzIDQuMDUxNzZIOS4zNzYxM1YzLjg1MTc2SDkuMTc2MTNWNC4wNTE3NlpNOC4zNTAxNyA0LjA1MTc2VjMuODUxNzZIOC4xNTAxN1Y0LjA1MTc2SDguMzUwMTdaTTguMzUwMTcgNy40NTgyM0w4LjIwMzkzIDcuNTk0NjVMOC41NTAxNyA3Ljk2NTgyVjcuNDU4MjNIOC4zNTAxN1pNNi43MjI4OSA1LjcxMzgyTDYuODY5MTMgNS41NzczOUw2LjcyODMxIDUuNDI2NDNMNi41ODE4OSA1LjU3MTk3TDYuNzIyODkgNS43MTM4MlpNNi4xMjc5MyA2LjMwNTIxTDUuOTg2OTMgNi4xNjMzN0w1Ljg0OTUyIDYuMjk5OTZMNS45ODE2OCA2LjQ0MTY0TDYuMTI3OTMgNi4zMDUyMVpNOC41NTAxNyAxMy4zODE2VjguNjg3NEg4LjE1MDE3VjEzLjM4MTZIOC41NTAxN1pNOS4xNzYxMyAxMy4xODE2SDguMzUwMTdWMTMuNTgxNkg5LjE3NjEzVjEzLjE4MTZaTTguOTc2MTMgOS41NzI4VjEzLjM4MTZIOS4zNzYxM1Y5LjU3MjhIOC45NzYxM1pNMTEuMjA0MSAxMS40NTM1TDkuMzIyMzggOS40MzYzOEw5LjAyOTg5IDkuNzA5MjNMMTAuOTExNiAxMS43MjY0TDExLjIwNDEgMTEuNDUzNVpNMTEuNTExOCAxMC44NTY3TDEwLjkxNjkgMTEuNDQ4MUwxMS4xOTg5IDExLjczMThMMTEuNzkzOCAxMS4xNDA0TDExLjUxMTggMTAuODU2N1pNOS4wMjk4OSA4LjQ4MDA2TDExLjUwNjYgMTEuMTM1TDExLjc5OTEgMTAuODYyMUw5LjMyMjM4IDguMjA3Mkw5LjAyOTg5IDguNDgwMDZaTTguOTc2MTMgNC4wNTE3NlY4LjM0MzYzSDkuMzc2MTNWNC4wNTE3Nkg4Ljk3NjEzWk04LjM1MDE3IDQuMjUxNzZIOS4xNzYxM1YzLjg1MTc2SDguMzUwMTdWNC4yNTE3NlpNOC41NTAxNyA3LjQ1ODIzVjQuMDUxNzZIOC4xNTAxN1Y3LjQ1ODIzSDguNTUwMTdaTTYuNTc2NjQgNS44NTAyNEw4LjIwMzkzIDcuNTk0NjVMOC40OTY0MiA3LjMyMThMNi44NjkxMyA1LjU3NzM5TDYuNTc2NjQgNS44NTAyNFpNNi4yNjg5MyA2LjQ0NzA2TDYuODYzODggNS44NTU2Nkw2LjU4MTg5IDUuNTcxOTdMNS45ODY5MyA2LjE2MzM3TDYuMjY4OTMgNi40NDcwNlpNOC40OTY0MiA4LjU1MDk3TDYuMjc0MTggNi4xNjg3OEw1Ljk4MTY4IDYuNDQxNjRMOC4yMDM5MyA4LjgyMzgzTDguNDk2NDIgOC41NTA5N1oiIGZpbGw9IiMyQzMzMzgiIG1hc2s9InVybCgjcGF0aC0zLW91dHNpZGUtMV8xOTUzNl81Njc5MykiLz4KPC9zdmc+Cg==',
    3
  );
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Dashboard',
    'Dashboard',
    'manage_options',
    'aesirx-bi-dashboard',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Acquisition',
    'Acquisition',
    'manage_options',
    'aesirx-bi-acquisition',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-acquisition',
    'AesirX BI Acquisition Search Engine',
    'Acquisition Search Engine',
    'manage_options',
    'aesirx-bi-acquisition-search-engines',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-acquisition',
    'AesirX BI Acquisition Campaigns',
    'Acquisition Campaigns',
    'manage_options',
    'aesirx-bi-acquisition-campaigns',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Behavior',
    'Behavior',
    'manage_options',
    'aesirx-bi-behavior',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Events',
    'Behavior Events',
    'manage_options',
    'aesirx-bi-behavior-events',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Events Generator',
    'Behavior Events Generator',
    'manage_options',
    'aesirx-bi-behavior-events-generator',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Outlinks',
    'Behavior Outlinks',
    'manage_options',
    'aesirx-bi-behavior-outlinks',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior User Flow',
    'Behavior User Flow',
    'manage_options',
    'aesirx-bi-behavior-users-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Consents',
    'Consent',
    'manage_options',
    'aesirx-bi-consents',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    4); 
  add_submenu_page(
    'aesirx-bi-consents',
    'AesirX BI Consents Template',
    'Consents Template',
    'manage_options',
    'aesirx-bi-consents-template',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    4);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI UTM Tracking',
    'Tracking',
    'manage_options',
    'aesirx-bi-utm-tracking',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    5);
  add_submenu_page(
    'aesirx-bi-utm-tracking',
    'AesirX BI UTM Tracking Generator',
    'UTM Tracking Generator',
    'manage_options',
    'aesirx-bi-utm-tracking-generator',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    5);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Visitors',
    'Visitors',
    'manage_options',
    'aesirx-bi-visitors',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Locations',
    'Locations',
    'manage_options',
    'aesirx-bi-visitors-locations',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Flow',
    'Flow',
    'manage_options',
    'aesirx-bi-visitors-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);

  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Platforms',
    'Platforms',
    'manage_options',
    'aesirx-bi-visitors-platforms',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Flow Detail',
    'Flow',
    'manage_options',
    'aesirx-bi-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI User Experience',
    'User Experience',
    'manage_options',
    'aesirx-bi-flow-list',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    7);
  $options = get_option('aesirx_analytics_plugin_options');
  if($options['track_ecommerce'] === "true") {
    add_submenu_page(
      'aesirx-bi-dashboard',
      'AesirX BI Woocommerce',
      'Woo',
      'manage_options',
      'aesirx-bi-woocommerce',
      function () {
        ?><div id="biapp" class="aesirxui"></div><?php
      },
      8);
    add_submenu_page(
      'aesirx-bi-woocommerce',
      'AesirX BI Woocommerce Product',
      'Woo Product',
      'manage_options',
      'aesirx-bi-woocommerce-product',
      function () {
        ?><div id="biapp" class="aesirxui"></div><?php
      },
      8);
  }
});

add_action('admin_init', 'aesirx_analytics_redirect_config', 1);
function aesirx_analytics_redirect_config() {
  if ( isset($_GET['page'])
       && ($_GET['page'] == 'aesirx-bi-dashboard' || $_GET['page'] == 'aesirx-bi-visitors' || $_GET['page'] == 'aesirx-bi-behavior' || $_GET['page'] == 'aesirx-bi-utm-tracking' || $_GET['page'] == 'aesirx-bi-woocommerce' || $_GET['page'] == 'aesirx-bi-consents')
       && !aesirx_analytics_config_is_ok()) {
    wp_redirect('/wp-admin/options-general.php?page=aesirx-analytics-plugin');
    die;
  }
}

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'toplevel_page_aesirx-bi-dashboard' || 
      $hook === 'toplevel_page_aesirx-bi-visitors' || 
      $hook === 'toplevel_page_aesirx-bi-behavior' || 
      $hook === 'toplevel_page_aesirx-bi-utm-tracking' || 
      $hook === 'toplevel_page_aesirx-bi-consents' || 
      $hook === 'toplevel_page_aesirx-bi-woocommerce' || 
      $hook === 'toplevel_page_aesirx-bi-acquisition' || 
      $hook === 'aesirx-bi_page_aesirx-bi-visitors' ||
      $hook === 'aesirx-bi_page_aesirx-bi-flow-list' ||
      $hook === 'admin_page_aesirx-bi-visitors-locations' || 
      $hook === 'admin_page_aesirx-bi-visitors-flow' || 
      $hook === 'admin_page_aesirx-bi-visitors-platforms' || 
      $hook === 'admin_page_aesirx-bi-flow' || 
      $hook === 'aesirx-bi_page_aesirx-bi-behavior' ||
      $hook === 'admin_page_aesirx-bi-behavior-events' ||
      $hook === 'admin_page_aesirx-bi-behavior-events-generator' ||
      $hook === 'admin_page_aesirx-bi-behavior-outlinks' ||
      $hook === 'admin_page_aesirx-bi-behavior-users-flow' ||
      $hook === 'aesirx-bi_page_aesirx-bi-utm-tracking' ||
      $hook === 'admin_page_aesirx-bi-utm-tracking-generator' ||
      $hook === 'aesirx-bi_page_aesirx-bi-consents' ||
      $hook === 'admin_page_aesirx-bi-consents-template' ||
      $hook === 'aesirx-bi_page_aesirx-bi-acquisition' ||
      $hook === 'admin_page_aesirx-bi-acquisition-search-engines' ||
      $hook === 'admin_page_aesirx-bi-acquisition-campaigns' ||
      $hook === 'aesirx-bi_page_aesirx-bi-woocommerce' ||
      $hook === 'admin_page_aesirx-bi-woocommerce-product') {

    $options = get_option('aesirx_analytics_plugin_options');

    $protocols = ['http://', 'https://'];
    $domain = str_replace($protocols, '', site_url());
    $streams = [['name' => get_bloginfo('name'), 'domain' => $domain]];
    $endpoint =
      ($options['storage'] ?? 'internal') == 'internal'
        ? get_bloginfo('url')
        : rtrim($options['domain'] ?? '', '/');

    $manifest = json_decode(
      file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
    );

    if ($manifest->entrypoints->bi->assets) {
      foreach ($manifest->entrypoints->bi->assets->js as $js) {
        wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, null, true);
      }
    }

    $clientId = $options['clientid'];
    $clientSecret = $options['secret'];

    $jwt = $options['storage'] === "external" ? 'window.env.REACT_APP_HEADER_JWT="true"' : '';

    wp_register_script( 'aesrix_bi_window', '', array(), null );

    wp_enqueue_script('aesrix_bi_window');

    wp_add_inline_script(
      'aesrix_bi_window',
      'window.env = {};
		  window.aesirxClientID = "' .  $clientId . '";
		  window.aesirxClientSecret = "' . $clientSecret . '";
		  window.env.REACT_APP_ENDPOINT_URL = "' . $endpoint . '";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(' . json_encode($streams) . ');
		  window.env.PUBLIC_URL= "' . plugin_dir_url(__DIR__) . '";
      window.env.STORAGE= "' . $options['storage'] . '";
      window.env.REACT_APP_WOOCOMMERCE_MENU= "' . $options['track_ecommerce'] . '";
      ' . $jwt,
    );
  }
});

function aesirx_analytics_escape_html($string) {
  $allowed_html = array(
    'input' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
        'checked' => array(),
     ),
     'a' => array(),
     'p' => array(),
     'h3' => array(),
     'div' => array(
        'class' => array(),
     ),
     'button' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
    ),
  );

  return wp_kses($string, $allowed_html);
}