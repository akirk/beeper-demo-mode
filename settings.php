<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$beeper_token = apply_filters( 'personal_crm_beeper_token', '' );
$fake_names   = apply_filters( 'personal_crm_demo_names', [ 'first' => [], 'last' => [] ] );
$fake_first   = $fake_names['first'] ?: [ 'Alice', 'Bob', 'Carol', 'David', 'Emma', 'Frank', 'Grace', 'Henry', 'Isabel', 'James', 'Kate', 'Liam', 'Maya', 'Noah', 'Olivia', 'Peter', 'Quinn', 'Rachel', 'Sam', 'Tara' ];
$fake_last    = $fake_names['last']  ?: [ 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Young', 'Clark' ];
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Beeper Demo Mode', 'beeper-demo-mode' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'demo_mode' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Demo Mode', 'beeper-demo-mode' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="demo_mode_enabled" value="1" <?php checked( get_option( 'demo_mode_enabled', false ) ); ?>>
						<?php esc_html_e( 'Replace real names with anonymous fake names', 'beeper-demo-mode' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'When enabled, names from Beeper are replaced with fictional names across all connected plugins. Useful for screenshots and demos.', 'beeper-demo-mode' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Image Whitelist', 'beeper-demo-mode' ); ?></h2>
	<p><?php esc_html_e( 'When demo mode is on, images are replaced with a placeholder. Images seen in this browser session appear below. Toggle "Load images" to reveal them, then whitelist the ones you want to show normally.', 'beeper-demo-mode' ); ?></p>

	<p>
		<button type="button" id="bdm-load-toggle" class="button"><?php esc_html_e( 'Load images', 'beeper-demo-mode' ); ?></button>
		<button type="button" id="bdm-clear-seen" class="button" style="margin-left:8px"><?php esc_html_e( 'Clear seen images', 'beeper-demo-mode' ); ?></button>
	</p>

	<div id="bdm-seen-images"></div>

	<hr>

	<h2><?php esc_html_e( 'Name Overrides', 'beeper-demo-mode' ); ?></h2>
	<p><?php esc_html_e( 'Names seen in demo mode are listed below. Edit the demo name, reset it to the auto-generated fake, or randomize it to a different one.', 'beeper-demo-mode' ); ?></p>

	<div id="bdm-seen-names"></div>

	<p>
		<button type="button" id="bdm-clear-names" class="button"><?php esc_html_e( 'Clear name overrides', 'beeper-demo-mode' ); ?></button>
	</p>

	<hr>

	<h2><?php esc_html_e( 'Developer Notes', 'beeper-demo-mode' ); ?></h2>
	<p><?php esc_html_e( 'Demo mode can also be enabled without this plugin by hooking the filter directly:', 'beeper-demo-mode' ); ?></p>
	<pre style="background:#f0f0f0;padding:12px;display:inline-block;">add_filter( 'personal_crm_demo_mode', '__return_true' );</pre>

	<p><?php esc_html_e( 'To replace the fake name lists:', 'beeper-demo-mode' ); ?></p>
	<pre style="background:#f0f0f0;padding:12px;display:inline-block;">add_filter( 'personal_crm_demo_names', function( $names ) {
    $names['first'] = [ 'Jean', 'Pierre', 'Marie', ... ];
    $names['last']  = [ 'Dupont', 'Martin', 'Bernard', ... ];
    return $names;
} );</pre>
</div>

<style>
#bdm-seen-images { margin-top: 12px; }
.bdm-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.bdm-card { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.bdm-card-img { width: 200px; height: 200px; object-fit: cover; border-radius: 4px; display: block; background: #e0e0e0; }
.bdm-card-placeholder { width: 200px; height: 200px; background: #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 48px; }
.bdm-card-placeholder.bdm-loading { font-size: 14px; color: #888; }
.bdm-card-placeholder.bdm-error { font-size: 13px; color: #d63638; }
#bdm-seen-names { margin-top: 8px; }
.bdm-name-table { border-collapse: collapse; max-width: 700px; width: 100%; }
.bdm-name-table th,
.bdm-name-table td { padding: 7px 10px; vertical-align: middle; border-bottom: 1px solid #ddd; }
.bdm-name-table th { background: #f6f7f7; text-align: left; font-weight: 600; }
.bdm-name-table input[type="text"] { width: 180px; }
.bdm-name-actions { display: flex; gap: 5px; }
</style>

<script>
(function() {
	var SEEN_KEY      = 'ctb_demo_seen_images';
	var WHITELIST_KEY = 'ctb_demo_whitelist';
	var BEEPER_TOKEN  = <?php echo wp_json_encode( $beeper_token ); ?>;
	var API_BASE      = 'http://localhost:23373/v1';
	var imagesLoaded  = false;
	var SEEN_NAMES_KEY    = 'bdm_seen_names';
	var NAME_OVERRIDES_KEY = 'bdm_name_overrides';
	var fakeFirstNames    = <?php echo wp_json_encode( $fake_first ); ?>;
	var fakeLastNames     = <?php echo wp_json_encode( $fake_last ); ?>;

	function getSeen() {
		try { return JSON.parse(localStorage.getItem(SEEN_KEY) || '[]'); } catch(e) { return []; }
	}

	function getWhitelist() {
		try { return JSON.parse(localStorage.getItem(WHITELIST_KEY) || '[]'); } catch(e) { return []; }
	}

	function setWhitelisted(mxcUrl, yes) {
		var list = getWhitelist();
		var idx  = list.indexOf(mxcUrl);
		if (yes && idx === -1) list.push(mxcUrl);
		if (!yes && idx !== -1) list.splice(idx, 1);
		localStorage.setItem(WHITELIST_KEY, JSON.stringify(list));
	}

	function fetchImageDataUrl(mxcUrl) {
		var url = API_BASE + '/assets/serve?url=' + encodeURIComponent(mxcUrl);
		return fetch(url, { headers: { 'Authorization': 'Bearer ' + BEEPER_TOKEN } })
			.then(function(r) {
				if (!r.ok) throw new Error(r.status);
				return r.blob();
			})
			.then(function(blob) {
				return new Promise(function(resolve, reject) {
					var reader       = new FileReader();
					reader.onloadend = function() { resolve(reader.result); };
					reader.onerror   = reject;
					reader.readAsDataURL(blob);
				});
			});
	}

	function buildCard(item) {
		var card = document.createElement('div');
		card.className = 'bdm-card';

		var imgArea = document.createElement('div');
		imgArea.className   = 'bdm-card-placeholder';
		imgArea.textContent = '\uD83D\uDDBC';
		card.appendChild(imgArea);

		var wlBtn = document.createElement('button');
		wlBtn.type = 'button';

		function updateWlBtn() {
			var wl = getWhitelist().indexOf(item.mxcUrl) !== -1;
			wlBtn.className   = 'button' + (wl ? ' button-primary' : '');
			wlBtn.textContent = wl
				? <?php echo wp_json_encode( __( '✓ Whitelisted', 'beeper-demo-mode' ) ); ?>
				: <?php echo wp_json_encode( __( 'Whitelist', 'beeper-demo-mode' ) ); ?>;
		}
		updateWlBtn();

		wlBtn.addEventListener('click', function() {
			setWhitelisted(item.mxcUrl, getWhitelist().indexOf(item.mxcUrl) === -1);
			updateWlBtn();
		});

		card.appendChild(wlBtn);
		card._imgArea = imgArea;
		card._mxcUrl  = item.mxcUrl;
		return card;
	}

	function loadCardImage(card) {
		var imgArea = card._imgArea;
		imgArea.className   = 'bdm-card-placeholder bdm-loading';
		imgArea.textContent = '\u2026';

		fetchImageDataUrl(card._mxcUrl)
			.then(function(dataUrl) {
				var img = document.createElement('img');
				img.src       = dataUrl;
				img.className = 'bdm-card-img';
				card.replaceChild(img, imgArea);
				card._imgArea = img;
			})
			.catch(function() {
				imgArea.className   = 'bdm-card-placeholder bdm-error';
				imgArea.textContent = <?php echo wp_json_encode( __( 'Failed to load', 'beeper-demo-mode' ) ); ?>;
			});
	}

	function render() {
		var seen      = getSeen();
		var container = document.getElementById('bdm-seen-images');
		if (!container) return;

		while (container.firstChild) container.removeChild(container.firstChild);
		imagesLoaded = false;
		updateLoadToggle();

		if (seen.length === 0) {
			var p = document.createElement('p');
			p.className   = 'description';
			p.textContent = <?php echo wp_json_encode( __( 'No images seen yet. Browse media in Chat to Blog to populate this list.', 'beeper-demo-mode' ) ); ?>;
			container.appendChild(p);
			return;
		}

		var grid = document.createElement('div');
		grid.className = 'bdm-grid';
		seen.forEach(function(item) { grid.appendChild(buildCard(item)); });
		container.appendChild(grid);
	}

	function loadAllImages() {
		var grid = document.querySelector('.bdm-grid');
		if (!grid) return;
		grid.querySelectorAll('.bdm-card').forEach(function(card) {
			if (card._imgArea && card._imgArea.tagName !== 'IMG') {
				loadCardImage(card);
			}
		});
	}

	function updateLoadToggle() {
		var btn = document.getElementById('bdm-load-toggle');
		if (!btn) return;
		if (imagesLoaded) {
			btn.className   = 'button button-primary';
			btn.textContent = <?php echo wp_json_encode( __( 'Images loaded', 'beeper-demo-mode' ) ); ?>;
		} else {
			btn.className   = 'button';
			btn.textContent = <?php echo wp_json_encode( __( 'Load images', 'beeper-demo-mode' ) ); ?>;
		}
		btn.disabled = !BEEPER_TOKEN || imagesLoaded;
	}

	function autoFakeName(real) {
		var sum = 0;
		for (var i = 0; i < real.length; i++) sum += real.charCodeAt(i);
		var first = fakeFirstNames[sum % fakeFirstNames.length];
		if (real.trim().indexOf(' ') !== -1) {
			return first + ' ' + fakeLastNames[(sum * 7 + 3) % fakeLastNames.length];
		}
		return first;
	}

	function randomFakeName(real) {
		var auto = autoFakeName(real);
		var result, tries = 0;
		do {
			var first = fakeFirstNames[Math.floor(Math.random() * fakeFirstNames.length)];
			if (real.trim().indexOf(' ') !== -1) {
				result = first + ' ' + fakeLastNames[Math.floor(Math.random() * fakeLastNames.length)];
			} else {
				result = first;
			}
			tries++;
		} while (result === auto && tries < 30);
		return result;
	}

	function getNameOverrides() {
		try { return JSON.parse(localStorage.getItem(NAME_OVERRIDES_KEY) || '{}'); } catch(e) { return {}; }
	}

	function setNameOverride(name, value) {
		var overrides = getNameOverrides();
		if (value === undefined || value === null || value === '') {
			delete overrides[name];
		} else {
			overrides[name] = value;
		}
		localStorage.setItem(NAME_OVERRIDES_KEY, JSON.stringify(overrides));
	}

	function getSeenNames() {
		try { return JSON.parse(localStorage.getItem(SEEN_NAMES_KEY) || '[]'); } catch(e) { return []; }
	}

	function renderNameRow(item) {
		var overrides   = getNameOverrides();
		var currentFake = overrides[item.name] !== undefined ? overrides[item.name] : autoFakeName(item.name);
		var isOverridden = overrides[item.name] !== undefined;

		var tr = document.createElement('tr');

		var tdReal = document.createElement('td');
		tdReal.textContent = item.name;
		tr.appendChild(tdReal);

		var tdFake = document.createElement('td');
		var input  = document.createElement('input');
		input.type      = 'text';
		input.value     = currentFake;
		input.className = 'regular-text';
		if (isOverridden) input.style.fontStyle = 'italic';
		input.addEventListener('input', function() {
			setNameOverride(item.name, this.value);
			input.style.fontStyle = 'italic';
		});
		tdFake.appendChild(input);
		tr.appendChild(tdFake);

		var tdActions = document.createElement('td');
		var actionsDiv = document.createElement('div');
		actionsDiv.className = 'bdm-name-actions';

		var randBtn = document.createElement('button');
		randBtn.type      = 'button';
		randBtn.className = 'button button-small';
		randBtn.textContent = '↺';
		randBtn.title     = <?php echo wp_json_encode( __( 'Randomize to a different fake name', 'beeper-demo-mode' ) ); ?>;
		randBtn.addEventListener('click', function() {
			var newName = randomFakeName(item.name);
			setNameOverride(item.name, newName);
			input.value          = newName;
			input.style.fontStyle = 'italic';
		});

		var realBtn = document.createElement('button');
		realBtn.type      = 'button';
		realBtn.className = 'button button-small';
		realBtn.textContent = '=';
		realBtn.title     = <?php echo wp_json_encode( __( 'Use real name', 'beeper-demo-mode' ) ); ?>;
		realBtn.addEventListener('click', function() {
			setNameOverride(item.name, item.name);
			input.value          = item.name;
			input.style.fontStyle = 'italic';
		});

		actionsDiv.appendChild(randBtn);
		actionsDiv.appendChild(realBtn);
		tdActions.appendChild(actionsDiv);
		tr.appendChild(tdActions);

		return tr;
	}

	function renderNamesTable() {
		var seen      = getSeenNames();
		var container = document.getElementById('bdm-seen-names');
		if (!container) return;

		while (container.firstChild) container.removeChild(container.firstChild);

		if (seen.length === 0) {
			var p = document.createElement('p');
			p.className   = 'description';
			p.textContent = <?php echo wp_json_encode( __( 'No names seen yet. Browse contacts or chats in demo mode to populate this list.', 'beeper-demo-mode' ) ); ?>;
			container.appendChild(p);
			return;
		}

		var groups  = seen.filter(function(n) { return n.type === 'group'; });
		var people  = seen.filter(function(n) { return n.type !== 'group'; });

		function makeSection(label, items) {
			if (items.length === 0) return;

			var h3 = document.createElement('h3');
			h3.textContent = label;
			container.appendChild(h3);

			var table = document.createElement('table');
			table.className = 'bdm-name-table';

			var thead = document.createElement('thead');
			var hrow  = document.createElement('tr');
			[
				<?php echo wp_json_encode( __( 'Real name', 'beeper-demo-mode' ) ); ?>,
				<?php echo wp_json_encode( __( 'Demo name', 'beeper-demo-mode' ) ); ?>,
				<?php echo wp_json_encode( __( 'Actions', 'beeper-demo-mode' ) ); ?>
			].forEach(function(label) {
				var th = document.createElement('th');
				th.textContent = label;
				hrow.appendChild(th);
			});
			thead.appendChild(hrow);
			table.appendChild(thead);

			var tbody = document.createElement('tbody');
			items.forEach(function(item) { tbody.appendChild(renderNameRow(item)); });
			table.appendChild(tbody);
			container.appendChild(table);
		}

		makeSection(<?php echo wp_json_encode( __( 'People', 'beeper-demo-mode' ) ); ?>, people);
		makeSection(<?php echo wp_json_encode( __( 'Groups', 'beeper-demo-mode' ) ); ?>, groups);
	}

	document.getElementById('bdm-load-toggle').addEventListener('click', function() {
		imagesLoaded = true;
		updateLoadToggle();
		loadAllImages();
	});

	document.getElementById('bdm-clear-seen').addEventListener('click', function() {
		localStorage.removeItem(SEEN_KEY);
		localStorage.removeItem(WHITELIST_KEY);
		render();
	});

	document.getElementById('bdm-clear-names').addEventListener('click', function() {
		localStorage.removeItem(SEEN_NAMES_KEY);
		localStorage.removeItem(NAME_OVERRIDES_KEY);
		renderNamesTable();
	});

	render();
	renderNamesTable();
})();
</script>
