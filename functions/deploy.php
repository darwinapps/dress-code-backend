<?php
// 1. Add a field in "Settings → General"
add_action('admin_init', function () {
  register_setting('general', 'my_curl_secret', [
    'type'              => 'string',
    'sanitize_callback' => 'sanitize_text_field',
    'default'           => '',
  ]);

  add_settings_field(
    'my_curl_secret',
    'CircleCI Secret',
    function () {
      $value = get_option('my_curl_secret', '');
      echo '<input type="text" id="my_curl_secret" name="my_curl_secret" value="' . esc_attr($value) . '" class="regular-text">';
      echo '<p class="description">Enter the secret for the CURL request</p>';
    },
    'general'
  );
});

// 2. Add an admin page with the button
add_action('admin_menu', function () {
  add_menu_page(
    'Deploy',
    'Deploy',
    'manage_options',
    'deploy-page',
    'render_deploy_page',
    'dashicons-cloud-upload',
    100
  );
});

function render_deploy_page() {
  $cooldown_until = get_transient('deploy_cooldown');
  $disabled = $cooldown_until ? 'disabled' : '';
?>
  <div class="wrap">
    <h1>Deploy</h1>
    <button id="deploy-btn" class="button button-primary" <?php echo $disabled; ?>>Deploy</button>
    <div id="deploy-result" style="margin-top:20px;"></div>

    <?php if ($cooldown_until):
      $remaining = $cooldown_until - time();
      $minutes = floor($remaining / 60);
      $seconds = $remaining % 60;
    ?>
      <div style="margin-bottom:15px;">
        Deploy button will be available in
        <?php echo $minutes . 'm ' . $seconds . 's'; ?>
      </div>
    <?php endif; ?>
  </div>
  <script type="text/javascript">
    document.getElementById('deploy-btn')?.addEventListener('click', function() {
      const btn = this;
      const resultBox = document.getElementById('deploy-result');
      resultBox.innerHTML = 'Deploy in progress...';
      btn.disabled = true;

      fetch(ajaxurl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=deploy_request'
        })
        .then(res => res.text())
        .then(data => {
          resultBox.innerHTML = '<strong>Result:</strong><br>' + data;
        })
        .catch(err => {
          resultBox.innerHTML = 'Error: ' + err;
        });
    });
  </script>
<?php
}

// 3. Ajax handler with 10-min cooldown
add_action('wp_ajax_deploy_request', function () {
  $secret = get_option('my_curl_secret', '');

  if (empty($secret)) {
    echo 'Error: secret is not set!';
    wp_die();
  }

  // Check cooldown
  $cooldown_until = get_transient('deploy_cooldown');
  if ($cooldown_until) {
    $remaining = $cooldown_until - time();
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    echo 'Error: You can deploy again in ' . $minutes . 'm ' . $seconds . 's';
    wp_die();
  }

  $url = "https://internal.circleci.com/private/soc/e/3f884124-a669-43e8-bafa-6c7865f79bce?secret=" . urlencode($secret);

  $args = [
    'headers' => ['Content-Type' => 'application/json'],
    'method'  => 'POST',
    'body'    => '{}',
    'timeout' => 30,
  ];

  $response = wp_remote_post($url, $args);

  if (is_wp_error($response)) {
    echo 'Error: ' . $response->get_error_message();
  } else {
    echo wp_remote_retrieve_body($response);
  }

  // Set cooldown until timestamp (10 min = 600 sec)
  $unlock_time = time() + 600;
  set_transient('deploy_cooldown', $unlock_time, 600);

  wp_die();
});
