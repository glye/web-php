<?php

// Sizing constants.
$margin_left = 300;
$margin_right = 50;
$header_height = 24;
$year_width = 120;
$branch_height = 30;
$branch_margin_height = 15;
$footer_height = 24;

$BRANCHES = [
    '1.7' => [
        'name' => 'eZ Platform 2017 LTS (v1.7)',
        'release-date' => '2016-12-01',
        'stable' => '2019-12-01',
        'security' => '2019-12-01',
        'release-type' => 'lts',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ez_platform_v1.7.0_lts/',
    ],
    '1.13' => [
        'name' => 'eZ Platform 2018 LTS (v1.13)',
        'release-date' => '2017-12-01',
        'stable' => '2020-12-01',
        'security' => '2021-12-01',
        'release-type' => 'lts',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ez_platform_v1.13.0_lts/',
    ],
    '2.5' => [
        'name' => 'eZ Platform 2019 LTS (v2.5)',
        'release-date' => '2019-03-01',
        'stable' => '2022-03-01',
        'security' => '2024-03-01',
        'release-type' => 'lts',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ez_platform_v2.5/',
    ],
    '3.3' => [
        'name' => 'Ibexa DXP 3.3 LTS',
        'release-date' => '2021-01-01',
        'stable' => '2024-01-01',
        'security' => '2026-01-01',
        'release-type' => 'lts',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ibexa_dxp_v3.3/',
    ],
    '4.1' => [
        'name' => 'Ibexa DXP 4.1 FT',
        'release-date' => '2022-04-1',
        'stable' => '2022-06-01',
        'security' => '2022-08-01',
        'release-type' => 'ft',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ibexa_dxp_v4.1/',
    ],
    '4.2' => [
        'name' => 'Ibexa DXP 4.2 FT',
        'release-date' => '2022-08-01',
        'stable' => '2022-11-01',
        'security' => '2022-12-01',
        'release-type' => 'ft',
        'release-type' => 'ft',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ibexa_dxp_v4.2/',
    ],
    '4.3' => [
        'name' => 'Ibexa DXP 4.3 FT',
        'release-date' => '2022-11-01',
        'stable' => '2023-02-01',
        'security' => '2023-03-01',
        'release-type' => 'ft',
        'release-url' => 'https://doc.ibexa.co/en/latest/release_notes/ibexa_dxp_v4.3/',
    ],
    '4.4' => [
        'name' => 'Ibexa DXP 4.4 FT',
        'release-date' => '2023-02-01',
        'stable' => '2023-05-01',
        'security' => '2023-06-01',
        'release-type' => 'ft',
        'release-url' => false,
    ],
    '4.5' => [
        'name' => 'Ibexa DXP 4.5 FT',
        'release-date' => '2023-04-13',
        'stable' => '2023-07-01',
        'security' => '2023-08-01',
        'release-type' => 'ft',
        'release-url' => false,
    ],
    '4.6' => [
        'name' => 'Ibexa DXP 4.6 LTS',
        'release-date' => '2023-06-01',
        'stable' => '2026-06-01',
        'security' => '2028-06-01',
        'release-type' => 'lts',
        'release-url' => false,
    ],
];

// TODO: Replace this with API call, ideally.
function get_all_branches() {
    return $GLOBALS['BRANCHES'];
}

function get_branch_name($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['name'])) {
        return $branches[$branch]['name'];
    }

    return $branch;
}

function get_branch_release_date($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['release-date'])) {
        return new DateTime($branches[$branch]['release-date']);
    }

    return false; // TODO log/exception
}

function get_branch_bug_eom_date($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['stable'])) {
        return new DateTime($branches[$branch]['stable']);
    }

    return false; // TODO log/exception
}

function get_branch_security_eol_date($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['security'])) {
        return new DateTime($branches[$branch]['security']);
    }

    return false; // TODO log/exception
}

function get_branch_release_type($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['release-type'])) {
        return $branches[$branch]['release-type'];
    }

    return false; // TODO log/exception
}

function get_branch_release_url($branch) {
    $branches = get_all_branches();

    if (isset($branches[$branch]['release-url'])) {
        return $branches[$branch]['release-url'];
    }

    return false; // TODO log/exception
}

function get_branch_support_state($branch) {
    $initial = get_branch_release_date($branch);
    $bug = get_branch_bug_eom_date($branch);
    $security = get_branch_security_eol_date($branch);
    $releaseType = get_branch_release_type($branch);

    if ($initial && $bug && $security) {
        $now = new DateTime;

        if ($now >= $security) {
            return 'eol';
        }

        if ($now >= $bug) {
            if ($releaseType === 'ft') {
                return 'security-ft';
            }

            return 'security-lts';
        }

        if ($now >= $initial) {
            if ($releaseType === 'ft') {
                return 'stable-ft';
            }

            return 'stable-lts';
        }

        return 'future';
    }

    return null;
}

function branches_to_show() {
    // Show all branches with EOL dates > min_date().
    $branches = [];

    foreach (get_all_branches() as $branch => $version) {
        if (get_branch_security_eol_date($branch) > min_date()) {
            $branches[$branch] = $version;
        }
    }

    return $branches;
}

function min_date() {
    $now = new DateTime('January 1');
    return $now->sub(new DateInterval('P3Y'));
}

function max_date() {
    $now = new DateTime('January 1');
    return $now->add(new DateInterval('P6Y'));
}

function date_horiz_coord(DateTime $date) {
    $diff = $date->diff(min_date());
    if (!$diff->invert) {
        return $GLOBALS['margin_left'];
    }
    return $GLOBALS['margin_left'] + ($diff->days / (365.24 / $GLOBALS['year_width']));
}

$branches = branches_to_show();
$i = 0;
foreach ($branches as $branch => $version) {
    $branches[$branch]['top'] = $header_height + (($branch_height + $branch_margin_height) * $i++);
}

if (!isset($non_standalone)) {
    header('Content-Type: image/svg+xml');
    echo '<?xml version="1.0"?>';
}

$years = iterator_to_array(new DatePeriod(min_date(), new DateInterval('P1Y'), max_date()));
$width = $margin_left + $margin_right + ((count($years) - 1) * $year_width);
$height = $header_height + $footer_height + (count($branches) * ($branch_height + $branch_margin_height) + $branch_margin_height);
?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewbox="0 0 <?php echo $width ?> <?php echo $height ?>" width="<?php echo $width ?>" height="<?php echo $height ?>">
	<style type="text/css">
		<![CDATA[
			text {
				fill: #151c25;
				font-family: "Work Sans", sans-serif;
				font-size: <?php echo (2 / 3) * $header_height; ?>px;
			}

			g.eol rect,
			.branches rect.eol {
				fill: #db0032;
			}

			g.eol text {
				fill: #ffffff;
			}

			g.security-lts rect,
			.branches rect.security-lts {
				fill: #ffc107;
			}

			g.security-ft rect,
			.branches rect.security-ft {
				fill: #ffda6a;
			}

			g.stable-lts rect,
			.branches rect.stable-lts {
				fill: #28a745;
			}

			g.stable-ft rect,
			.branches rect.stable-ft {
				fill: #7eca8f;
			}

			g.future rect,
			.branches rect.future {
				fill: #e5e4e2;
			}

			.branch-labels text {
				dominant-baseline: central;
				text-anchor: middle;
			}

			.branches a:hover,
			.branch-labels a:hover {
				stroke-width: 3px;
				stroke: #007bff;
				cursor: pointer;
				text-decoration: none;
			}

			.branch-labels a:hover text {
				stroke-width: 0;
			}

			.branch-dividers line {
				stroke: #151c25;
				stroke-dasharray: 7,7;
				filter: opacity(30%);
			}

			.today line {
				stroke: #db0032;
				stroke-dasharray: 7,12;
				stroke-width: 3px;
				stroke-linecap: round;
			}

			.today text {
				fill: #db0032;
				text-anchor: middle;
			}

			.years line {
				stroke: #151c25;
			}

			.years text {
				text-anchor: middle;
			}
		]]>
	</style>

	<!-- Branch divider lines -->
	<g class="branch-dividers">
		<?php foreach ($branches as $branch => $version): ?>
			<line x1="0" y1="<?php echo $version['top'] + ($branch_margin_height/2) ?>" x2="<?php echo $width ?>" y2="<?php echo $version['top'] + ($branch_margin_height/2) ?>" />
		<?php endforeach ?>
	</g>

	<!-- Year lines -->
	<g class="years">
		<?php foreach ($years as $date): ?>
			<line x1="<?php echo date_horiz_coord($date) ?>" y1="<?php echo $header_height ?>" x2="<?php echo date_horiz_coord($date) ?>" y2="<?php echo $header_height + (count($branches) * ($branch_height + $branch_margin_height)) ?>" />
			<text x="<?php echo date_horiz_coord($date) ?>" y="<?php echo 0.8 * $header_height; ?>">
				<?php echo $date->format('Y') ?>
			</text>
		<?php endforeach ?>
	</g>

	<!-- Branch labels -->
	<g class="branch-labels">
		<?php foreach ($branches as $branch => $version): ?>
			<?php
			$x_release_url = get_branch_release_url($branch);
			?>
			<g class="<?php echo get_branch_support_state($branch) ?>">
				<?php if (is_string($x_release_url)): ?><a xlink:href="<?php echo $x_release_url ?>" target="_blank"><?php endif ?>
					<rect x="0" y="<?php echo $version['top'] + $branch_margin_height ?>" width="<?php echo 0.8 * $margin_left ?>" height="<?php echo $branch_height ?>" />
					<text x="<?php echo 0.4 * $margin_left ?>" y="<?php echo $version['top'] + $branch_margin_height + (0.5 * $branch_height) ?>">
						<?php echo htmlspecialchars(get_branch_name($branch)) ?>
					</text>
				<?php if (is_string($x_release_url)): ?></a><?php endif ?>
			</g>
		<?php endforeach ?>
	</g>

	<!-- Branch blocks -->
	<g class="branches">
		<?php foreach ($branches as $branch => $version): ?>
			<?php
			$x_release = date_horiz_coord(get_branch_release_date($branch));
			$x_bug = date_horiz_coord(get_branch_bug_eom_date($branch));
			$x_eol = date_horiz_coord(get_branch_security_eol_date($branch));
			$x_release_type = get_branch_release_type($branch);
			$x_release_url = get_branch_release_url($branch);
			?>
			<?php if (is_string($x_release_url)): ?><a xlink:href="<?php echo $x_release_url ?>" target="_blank"><?php endif ?>
				<rect class="stable-<?php echo $x_release_type ?>" x="<?php echo $x_release ?>" y="<?php echo $version['top'] + $branch_margin_height ?>" width="<?php echo $x_bug - $x_release ?>" height="<?php echo $branch_height ?>" />
				<rect class="security-<?php echo $x_release_type ?>" x="<?php echo $x_bug ?>" y="<?php echo $version['top'] + $branch_margin_height ?>" width="<?php echo $x_eol - $x_bug ?>" height="<?php echo $branch_height ?>" />
			<?php if (is_string($x_release_url)): ?></a><?php endif ?>
		<?php endforeach ?>
	</g>

	<!-- Today -->
	<g class="today">
		<?php
        $now = new DateTime;
        $x = date_horiz_coord($now);
        ?>
		<line x1="<?php echo $x ?>" y1="<?php echo $header_height ?>" x2="<?php echo $x ?>" y2="<?php echo $header_height + $branch_margin_height + (count($branches) * ($branch_height + $branch_margin_height)) ?>" />
		<text x="<?php echo $x ?>" y="<?php echo $header_height + $branch_margin_height + (count($branches) * ($branch_height + $branch_margin_height)) + (0.8 * $footer_height) ?>">
			<?php echo 'Today: ' . $now->format('j M Y') ?>
		</text>
	</g>
</svg>
