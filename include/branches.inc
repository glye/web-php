<?php
include_once __DIR__ . '/releases.inc';
include_once __DIR__ . '/version.inc';

/* Branch overrides. For situations where we've changed the exact dates for a
 * branch's active support and security fix EOLs, these can be reflected here.
 *
 * Supported keys are:
 *    - stable:   the end of active support (usually two years after release).
 *    - security: the end of security support (usually release + 3 years).
 */
$BRANCHES = [
    '1.7' => [
        'name' => 'eZ Platform 2017 LTS (v1.7)',
        'stable' => '2019-12-01',
        'security' => '2019-12-01',
    ],
    '1.13' => [
        'name' => 'eZ Platform 2018 LTS (v1.13)',
        'stable' => '2020-12-01',
        'security' => '2021-12-01',
    ],
    '2.5' => [
        'name' => 'eZ Platform 2019 LTS (v2.5)',
        'stable' => '2022-03-01',
        'security' => '2024-03-01',
    ],
    '3.3' => [
        'name' => 'Ibexa DXP 3.3 LTS',
        'stable' => '2024-01-01',
        'security' => '2026-01-01',
    ],
    '4.1' => [
        'name' => 'Ibexa DXP 4.1 FT',
        'stable' => '2022-06-01',
        'security' => '2022-08-01',
    ],
    '4.2' => [
        'name' => 'Ibexa DXP 4.2 FT',
        'stable' => '2022-11-01',
        'security' => '2022-12-01',
    ],
    '4.3' => [
        'name' => 'Ibexa DXP 4.3 FT',
        'stable' => '2023-02-01',
        'security' => '2023-03-01',
    ],
    '4.4' => [
        'name' => 'Ibexa DXP 4.4 FT',
        'stable' => '2023-05-01',
        'security' => '2023-06-01',
    ],
    '4.5' => [
        'name' => 'Ibexa DXP 4.5 FT',
        'stable' => '2023-08-01',
        'security' => '2023-09-01',
    ],
    '4.6' => [
        'name' => 'Ibexa DXP 4.6 LTS',
        'stable' => '2026-06-01',
        'security' => '2028-06-01',
    ],
];

/* Time to keep EOLed branches in the array returned by get_active_branches(),
 * which is used on the front page download links and the supported versions
 * page. (Currently 28 days.) */
$KEEP_EOL = new DateInterval('P28D');

function format_interval($from, $to) {
    try {
        $from_obj = $from instanceof DateTime ? $from : new DateTime($from);
        $to_obj = $to instanceof DateTime ? $to : new DateTime($to);
        $diff = $to_obj->diff($from_obj);

        $times = [];
        if ($diff->y) {
            $times[] = [$diff->y, 'year'];
            if ($diff->m) {
                    $times[] = [$diff->m, 'month'];
            }
        } elseif ($diff->m) {
            $times[] = [$diff->m, 'month'];
        } elseif ($diff->d) {
            $times[] = [$diff->d, 'day'];
        } else {
            $eolPeriod = 'midnight';
        }
        if ($times) {
            $eolPeriod = implode(', ',
                                        array_map(
                                            function ($t) {
                                                return "$t[0] $t[1]" .
                                                        ($t[0] != 1 ? 's' : '');
                                            },
                                            $times
                                        )
                                );

            if ($diff->invert) {
                $eolPeriod = "$eolPeriod ago";
            } else {
                $eolPeriod = "in $eolPeriod";
            }
        }
    } catch (Exception $e) {
        $eolPeriod = 'unknown';
    }

    return $eolPeriod;
}

function version_number_to_branch(string $version): ?string {
    $parts = explode('.', $version);
    if (count($parts) > 1) {
        return "$parts[0].$parts[1]";
    }

    return null;
}

function get_all_branches() {
    $branches = [];

    foreach ($GLOBALS['OLDRELEASES'] as $major => $releases) {
        foreach ($releases as $version => $release) {
            if ($branch = version_number_to_branch($version)) {
                if (!isset($branches[$major][$branch]) || version_compare($version, $branches[$major][$branch]['version'], 'gt')) {
                    $branches[$major][$branch] = $release;
                    $branches[$major][$branch]['version'] = $version;
                }
            }
        }
    }

    foreach ($GLOBALS['RELEASES'] as $major => $releases) {
        foreach ($releases as $version => $release) {
            if ($branch = version_number_to_branch($version)) {
                if (!isset($branches[$major][$branch]) || version_compare($version, $branches[$major][$branch]['version'], 'gt')) {
                    $branches[$major][$branch] = $release;
                    $branches[$major][$branch]['version'] = $version;
                }
            }
        }
    }

    krsort($branches);
    foreach ($branches as &$branch) {
        krsort($branch);
    }

    return $branches;
}

function get_active_branches($include_recent_eols = true) {
    $branches = [];
    $now = new DateTime;

    foreach ($GLOBALS['RELEASES'] as $major => $releases) {
        foreach ($releases as $version => $release) {
            if ($branch = version_number_to_branch($version)) {
                $threshold = get_branch_security_eol_date($branch);
                if ($threshold === null) {
                    // No EOL date available, assume it is ancient.
                    continue;
                }
                if ($include_recent_eols) {
                    $threshold->add($GLOBALS['KEEP_EOL']);
                }
                if ($now < $threshold) {
                    $branches[$major][$branch] = $release;
                    $branches[$major][$branch]['version'] = $version;
                }
            }
        }
        if (!empty($branches[$major])) {
            ksort($branches[$major]);
        }
    }

    ksort($branches);
    return $branches;
}

/* If you provide an array to $always_include, note that the version numbers
 * must be in $RELEASES _and_ must be the full version number, not the branch:
 * ie provide array('5.3.29'), not array('5.3'). */
function get_eol_branches($always_include = null) {
    $always_include = $always_include ?: [];
    $branches = [];
    $now = new DateTime;

    // Gather the last release on each branch into a convenient array.
    foreach ($GLOBALS['OLDRELEASES'] as $major => $releases) {
        foreach ($releases as $version => $release) {
            if ($branch = version_number_to_branch($version)) {
                if (!isset($branches[$major][$branch]) || version_compare($version, $branches[$major][$branch]['version'], 'gt')) {
                    $branches[$major][$branch] = [
                        'date' => strtotime($release['date']),
                        'link' => "/releases#$version",
                        'version' => $version,
                    ];
                }
            }
        }
    }

    /* Exclude releases from active branches, where active is defined as "in
     * the $RELEASES array and not explicitly marked as EOL there". */
    foreach ($GLOBALS['RELEASES'] as $major => $releases) {
        foreach ($releases as $version => $release) {
            if ($branch = version_number_to_branch($version)) {
                if ($now < get_branch_security_eol_date($branch)) {
                    /* This branch isn't EOL: remove it from our array. */
                    if (isset($branches[$major][$branch])) {
                        unset($branches[$major][$branch]);
                    }
                } else {
                    /* Add the release information to the EOL branches array, since it
                     * should be newer than the information we got from $OLDRELEASES. */
                    $always_include[] = $version;
                }
            }
        }
    }

    // Include any release in the always_include list that's in $RELEASES.
    if ($always_include) {
        foreach ($always_include as $version) {
            $parts = explode('.', $version);
            $major = $parts[0];

            if (isset($GLOBALS['RELEASES'][$major][$version])) {
                $release = $GLOBALS['RELEASES'][$major][$version];
                if ($branch = version_number_to_branch($version)) {
                    $branches[$major][$branch] = [
                        'date' => strtotime($release['source'][0]['date']),
                        'link' => "/downloads#v$version",
                        'version' => $version,
                    ];
                }
            }
        }
    }

    krsort($branches);
    foreach ($branches as &$branch) {
        krsort($branch);
    }

    return $branches;
}

/* $branch is expected to have at least two components: MAJOR.MINOR or
 * MAJOR.MINOR.REVISION (the REVISION will be ignored if provided). This will
 * return either null (if no release exists on the given branch), or the usual
 * version metadata from $RELEASES for a single release. */
function get_initial_release($branch) {
    $branch = version_number_to_branch($branch);
    if (!$branch) {
        return null;
    }
    $major = substr($branch, 0, strpos($branch, '.'));

    if (isset($GLOBALS['OLDRELEASES'][$major]["$branch.0"])) {
        return $GLOBALS['OLDRELEASES'][$major]["$branch.0"];
    }

    /* If there's only been one release on the branch, it won't be in
     * $OLDRELEASES yet, so let's check $RELEASES. */
    if (isset($GLOBALS['RELEASES'][$major]["$branch.0"])) {
        // Fake a date like we have on the oldreleases array.
        $release = $GLOBALS['RELEASES'][$major]["$branch.0"];
        $release['date'] = $release['source'][0]['date'];
        return $release;
    }

    // Shrug.
    return null;
}

function get_final_release($branch) {
    $branch = version_number_to_branch($branch);
    if (!$branch) {
        return null;
    }
    $major = substr($branch, 0, strpos($branch, '.'));

    $last = "$branch.0";
    foreach ($GLOBALS['OLDRELEASES'][$major] as $version => $release) {
        if (version_number_to_branch($version) == $branch && version_compare($version, $last, '>')) {
            $last = $version;
        }
    }

    if (isset($GLOBALS['OLDRELEASES'][$major][$last])) {
        return $GLOBALS['OLDRELEASES'][$major][$last];
    }

    /* If there's only been one release on the branch, it won't be in
     * $OLDRELEASES yet, so let's check $RELEASES. */
    if (isset($GLOBALS['RELEASES'][$major][$last])) {
        // Fake a date like we have on the oldreleases array.
        $release = $GLOBALS['RELEASES'][$major][$last];
        $release['date'] = $release['source'][0]['date'];
        return $release;
    }

    // Shrug.
    return null;
}

function get_branch_name($branch) {
    if (isset($GLOBALS['BRANCHES'][$branch]['name'])) {
        return $GLOBALS['BRANCHES'][$branch]['name'];
    }

    return $branch;
}

function get_branch_bug_eol_date($branch) {
    if (isset($GLOBALS['BRANCHES'][$branch]['stable'])) {
        return new DateTime($GLOBALS['BRANCHES'][$branch]['stable']);
    }

    $date = get_branch_release_date($branch);

    return $date ? $date->add(new DateInterval('P2Y')) : null;
}

function get_branch_security_eol_date($branch) {
    if (isset($GLOBALS['BRANCHES'][$branch]['security'])) {
        return new DateTime($GLOBALS['BRANCHES'][$branch]['security']);
    }

    /* Versions before 5.3 are based solely on the final release date in
     * $OLDRELEASES. */
    if (version_compare($branch, '5.3', '<')) {
        $release = get_final_release($branch);

        return $release ? new DateTime($release['date']) : null;
    }

    $date = get_branch_release_date($branch);
    return $date ? $date->add(new DateInterval('P3Y')) : null;
}

function get_branch_release_date($branch) {
    $initial = get_initial_release($branch);

    return $initial ? new DateTime($initial['date']) : null;
}

function get_branch_support_state($branch) {
    $initial = get_branch_release_date($branch);
    $bug = get_branch_bug_eol_date($branch);
    $security = get_branch_security_eol_date($branch);

    if ($initial && $bug && $security) {
        $now = new DateTime;

        if ($now >= $security) {
            return 'eol';
        }

        if ($now >= $bug) {
            return 'security';
        }

        if ($now >= $initial) {
            return 'stable';
        }

        return 'future';
    }

    return null;
}

function compare_version(array $arrayA, string $versionB)
{
    $arrayB = version_array($versionB, count($arrayA));

    foreach ($arrayA as $index => $componentA) {
        $componentA = $arrayA[$index];
        $componentB = $arrayB[$index];
        if ($componentA != $componentB) {
            return $componentA > $componentB ? 1 : -1;
        }
    }

    return 0;
}

function version_array(string $version, ?int $length = null)
{
    $versionArray = array_map(
        'intval',
        explode('.', $version)
    );

    if (is_int($length)) {
        $versionArray = count($versionArray) < $length
            ? array_pad($versionArray, $length, 0)
            : array_slice(
                $versionArray,
                0,
                $length
            );
    }

    return $versionArray;
}

function get_current_release_for_branch(int $major, ?int $minor): ?string {
    global $RELEASES, $OLDRELEASES;

    $prefix = "{$major}.";
    if ($minor !== null) {
        $prefix .= "{$minor}.";
    }

    foreach (($RELEASES[$major] ?? []) as $version => $_) {
        if (!strncmp($prefix, $version, strlen($prefix))) {
            return $version;
        }
    }

    foreach (($OLDRELEASES[$major] ?? []) as $version => $_) {
        if (!strncmp($prefix, $version, strlen($prefix))) {
            return $version;
        }
    }

    return null;
}
