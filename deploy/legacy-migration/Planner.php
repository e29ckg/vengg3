<?php
declare(strict_types=1);

/** Pure transformation: no database writes, so the exact plan can be tested offline. */
final class LegacyMigrationPlanner
{
    public array $rows = [];
    public array $maps = [];
    public array $errors = [];
    public array $warnings = [];
    private array $next = [];

    private function fail(string $message): void { $this->errors[] = $message; }
    private function add(string $table, array $row): void { $this->rows[$table][] = $row; }
    private function ref(string $table, mixed $id): mixed { return $this->maps[$table][(string)$id] ?? null; }
    private function allocate(string $table, mixed $old): int
    {
        $id = ++$this->next[$table];
        if ($id > 2147483647) throw new RuntimeException("Target INT capacity exceeded: $table");
        return $this->maps[$table][(string)$old] = $id;
    }
    private static function date(mixed $value, string $format): bool
    {
        if (!is_string($value)) return false;
        $d = DateTimeImmutable::createFromFormat('!' . $format, $value);
        return $d !== false && $d->format($format) === $value;
    }
    private function timestamp(mixed $value, string $context): ?string
    {
        if ($value === null || $value === '') return null;
        if (!self::date($value, 'Y-m-d H:i:s')) {
            $this->fail("Invalid timestamp: $context");
            return null;
        }
        return $value;
    }

    public function build(array $source, array $target, array $config): void
    {
        $this->rows = $this->maps = $this->errors = $this->warnings = $this->next = [];
        foreach (['ven_name','ven_name_sub','ven_user','ven_com','ven_schedule','ven_change','ven_time'] as $table) {
            $ids = array_column($target[$table] ?? [], 'id');
            $this->next[$table] = $ids ? (int)max($ids) : 0;
        }
        $users = [];
        foreach ($source['user'] as $u) {
            $old = (string)$u['id'];
            $id = substr(hash('sha256', $config['source_database'] . ':user:' . $old), 0, 32);
            $name = $config['username_overrides'][$old] ?? $u['username'];
            if ($name === '' || strlen($name) > 255) $this->fail("Invalid username override: user:$old");
            // Let the database's collation check case/accent equivalence in the runner too.
            foreach ($target['user'] ?? [] as $existing) {
                if ($existing['id'] === $id) $this->fail("Target user ID collision: user:$old");
                if (strcasecmp($existing['username'], $name) === 0) $this->fail("Username collision: user:$old (set username_overrides)");
            }
            if (isset($users[strtolower($name)])) $this->fail("Duplicate source username: user:$old");
            $users[strtolower($name)] = true;
            $this->maps['user'][$old] = $id;
            if (!in_array((int)$u['role'], [1,2,3,9], true)) $this->fail("Unknown role: user:$old");
            if (!in_array((int)$u['status'], [0,10,77], true)) $this->fail("Unknown status: user:$old");
            if ((password_get_info($u['password_hash'])['algoName'] ?? 'unknown') === 'unknown') $this->fail("Unsupported password hash: user:$old");
            $row = array_intersect_key($u, array_flip(['username','password_hash','email','role']));
            $this->add('user', $row + ['id'=>$id, 'status'=>(int)$u['status'] === 10 ? 10 : 0,
                'is_deleted'=>(int)$u['status'] === 77 ? 1 : 0, 'auth_key'=>null, 'password_reset_token'=>null,
                'created_at'=>$this->timestamp($u['created_at'], "user:$old.created_at"), 'updated_at'=>$this->timestamp($u['updated_at'], "user:$old.updated_at")]);
            $last = array_key_last($this->rows['user']);
            $this->rows['user'][$last]['username'] = $name;
        }
        $sourceUsers = array_column($source['user'], null, 'id');
        $seenProfiles = [];
        foreach ($source['profile'] as $p) {
            $old = (string)$p['user_id'];
            if (!$this->ref('user', $old) || isset($seenProfiles[$old])) { $this->fail("Missing/duplicate profile user:$old"); continue; }
            $seenProfiles[$old] = true;
            $row = array_intersect_key($p, array_flip(['id_card','birthday','bloodtype','address','phone','bank_account','bank_comment','st']));
            if (!empty($p['birthday']) && !self::date($p['birthday'], 'Y-m-d')) $this->fail("Invalid birthday: user:$old");
            $this->add('profile', $row + ['user_id'=>$this->ref('user',$old), 'prefix_name'=>$p['fname'],
                'first_name'=>$p['name'], 'last_name'=>$p['sname'], 'position'=>$p['dep'], 'department'=>$p['workgroup'],
                // Paths must be verified separately; never point at an un-copied legacy upload.
                'avatar'=>$config['avatar_overrides'][$old] ?? null, 'srt'=>999,
                'status'=>(int)$sourceUsers[$old]['status'] === 10 ? 10 : 0,
                'created_at'=>$this->timestamp($p['created_at'], "profile:$old"), 'updated_at'=>$this->timestamp($p['updated_at'], "profile:$old")]);
        }
        foreach ($source['user'] as $u) if (!isset($seenProfiles[(string)$u['id']])) $this->fail('Missing profile: user:' . $u['id']);

        $periods = $config['shift_periods'] ?? [];
        foreach ($source['ven_name'] as $n) {
            $dn = trim((string)$n['DN']);
            if (!isset($periods[$dn]) || !preg_match('/\A(?:[01][0-9]|2[0-3])\.[0-5][0-9]-(?:[01][0-9]|2[0-3])\.[0-5][0-9]\z/', $periods[$dn])) {
                $this->fail('Missing/invalid shift_periods for ven_name:' . $n['id']); continue;
            }
            $this->add('ven_name', ['id'=>$this->allocate('ven_name',$n['id']), 'name'=>$n['name'], 'name_full'=>$n['name_full'],
                'dn'=>$dn . '(' . $periods[$dn] . ')', 'srt'=>$n['srt'], 'status'=>$n['st'], 'word'=>null, 'google_calendar_id'=>null]);
        }
        foreach (array_unique(array_column($source['ven_name'], 'DN')) as $dn) {
            $dn = trim((string)$dn);
            if (!isset($periods[$dn])) continue;
            $exists = false;
            foreach ($target['ven_time'] ?? [] as $t) if ($t['name_th'] === $dn && $t['time_period'] === $periods[$dn]) $exists = true;
            if (!$exists) $this->add('ven_time', ['id'=>$this->allocate('ven_time',$dn), 'name_th'=>$dn, 'time_period'=>$periods[$dn], 'srt'=>99]);
        }
        $subs = $source['ven_name_sub'];
        $subIds = array_column($subs, 'id');
        foreach ($config['restore_subs'] ?? [] as $sub) {
            foreach (['id','ven_name_id','name','price','color','srt','st'] as $key) if (!array_key_exists($key,$sub)) throw new RuntimeException("restore_subs missing field:$key");
            if (in_array($sub['id'], $subIds)) { $this->fail('restore_subs ID already exists:' . $sub['id']); continue; }
            $subIds[] = $sub['id'];
            $subs[] = $sub;
        }
        foreach ($subs as $s) {
            $main = $this->ref('ven_name',$s['ven_name_id']);
            if (!$main) { $this->fail('Missing main for sub:' . $s['id']); continue; }
            $this->add('ven_name_sub', ['id'=>$this->allocate('ven_name_sub',$s['id']), 'ven_name_id'=>$main,
                'name'=>$s['name'], 'price'=>$s['price'], 'color'=>$s['color'], 'srt'=>$s['srt'], 'status'=>$s['st']]);
        }
        foreach ($source['ven_user'] as $v) {
            $user = $this->ref('user',$v['user_id']); $sub = $this->ref('ven_name_sub',$v['vns_id']);
            if (!$user || !$sub) { $this->fail('Missing member reference:' . $v['vu_id']); continue; }
            $this->add('ven_user', ['id'=>$this->allocate('ven_user',$v['vu_id']), 'user_id'=>$user, 'ven_name_sub_id'=>$sub,
                'order_num'=>$v['order'], 'srt'=>$v['order'] ?? 999, 'comment'=>$v['comment'],
                'create_at'=>$this->timestamp($v['create_at'], 'ven_user:' . $v['vu_id'])]);
        }

        $days = [];
        foreach ($source['ven'] as $v) if (in_array((int)$v['status'], [1,2], true) && self::date($v['ven_date'],'Y-m-d')) {
            $days[(string)$v['ven_com_idb']][(int)substr($v['ven_date'],8,2)] = true;
        }
        foreach ($source['ven_com'] as $c) {
            $main = $this->ref('ven_name',$c['vn_id']);
            if (!$main) { $this->fail('Missing command main:' . $c['id']); continue; }
            if (!self::date($c['ven_month'],'Y-m') || !self::date($c['ven_com_date'],'Y-m-d')) $this->fail('Invalid command date:' . $c['id']);
            if (!in_array((string)$c['status'], ['0','1'], true)) $this->fail('Unknown command status:' . $c['id']);
            $d = array_keys($days[(string)$c['id']] ?? []); sort($d, SORT_NUMERIC);
            $this->add('ven_com', ['id'=>$this->allocate('ven_com',$c['id']), 'com_num'=>$c['ven_com_num'], 'com_date'=>$c['ven_com_date'],
                'ven_month'=>$c['ven_month'], 'ven_name_id'=>$main, 'ven_com_days'=>implode(',',$d), 'status'=>$c['status'],
                'comment'=>$c['comment'], 'file'=>null, 'ref'=>$c['ref'], 'create_at'=>$this->timestamp($c['create_at'], 'ven_com:' . $c['id'])]);
        }
        $subLookup = array_column($subs,null,'id');
        $mainLookup = array_column($source['ven_name'],null,'id');
        $comLookup = array_column($source['ven_com'],null,'id');
        foreach ($source['ven'] as $v) if (in_array((int)$v['status'],[1,2],true) && isset($subLookup[$v['vns_id']],$comLookup[$v['ven_com_idb']])) {
            $s = $subLookup[$v['vns_id']]; $c = $comLookup[$v['ven_com_idb']];
            if ((string)$v['vn_id'] !== (string)$s['ven_name_id'] || (string)$c['vn_id'] !== (string)$s['ven_name_id']) $this->fail('Schedule main/sub/command mismatch:' . $v['id']);
            if ((float)$v['price'] !== (float)$s['price']) $this->fail('Historical rate differs from target duty rate:' . $v['id']);
            if ((string)$v['DN'] !== (string)($mainLookup[$v['vn_id']]['DN'] ?? '')) $this->fail('Schedule shift mismatch:' . $v['id']);
            if (substr($v['ven_date'],0,7) !== $c['ven_month']) $this->fail('Schedule outside command month:' . $v['id']);
        }
        $this->schedules($source, $config);
        $this->options($source, $target);
        if (isset($config['agency_settings'])) {
            $a = $config['agency_settings'];
            $this->add('agency_settings', ['id'=>1, 'agency_name'=>$a['agency_name'],
                'directors'=>json_encode($a['directors'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),
                'admins'=>json_encode($a['admins'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),
                'finances'=>json_encode($a['finances'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        }
        $this->warnings[] = 'Legacy snapshots are archived. Uploads, signers, Google and Telegram require the documented separate setup; avatars/command files are NULL unless configured.';
    }

    private function schedules(array $source, array $config): void
    {
        $ven = array_column($source['ven'], null, 'id');
        $parent = array_combine(array_keys($ven), array_keys($ven));
        $find = static function ($id) use (&$parent): mixed {
            while ($parent[$id] != $id) { $parent[$id] = $parent[$parent[$id]]; $id = $parent[$id]; }
            return $id;
        };
        $edges = []; $in = []; $out = [];
        $changes = $source['ven_change'];
        usort($changes, static fn($a,$b) => [$a['create_at'],(string)$a['id']] <=> [$b['create_at'],(string)$b['id']]);
        foreach ($changes as $c) {
            if ((string)$c['status'] === '77') continue; // Archive only: cancellation is not rejection.
            if (!in_array((string)$c['status'], ['1','2'],true)) { $this->fail('Unknown change status:' . $c['id']); continue; }
            foreach ([1,2] as $side) {
                $old = $c["ven_id{$side}_old"]; $new = $c["ven_id{$side}"];
                if ($side === 2 && !$old && !$new) continue;
                if (!$old || !$new || !isset($ven[$old],$ven[$new]) || $old == $new) { $this->fail('Invalid change edge:' . $c['id'] . ":$side"); continue; }
                $a = $ven[$old]; $b = $ven[$new];
                foreach (['ven_date','ven_com_idb','vns_id','vn_id'] as $field) if ((string)$a[$field] !== (string)$b[$field]) $this->fail('Change changes duty slot:' . $c['id'] . ":$field");
                $ownerBefore = $side === 1 ? $c['user_id1'] : $c['user_id2'];
                $ownerAfter = $side === 1 ? $c['user_id2'] : $c['user_id1'];
                if ((string)$a['user_id'] !== (string)$ownerBefore || (string)$b['user_id'] !== (string)$ownerAfter) $this->fail('Change owner mismatch:' . $c['id'] . ":$side");
                if (isset($out[$old]) || isset($in[$new]) || $find($old) == $find($new)) $this->fail('Branched/cyclic change chain:' . $c['id']);
                $parent[$find($new)] = $find($old);
                $out[$old] = $new; $in[$new] = $old; $edges[(string)$c['id']][$side] = $new;
            }
        }
        $groups = [];
        foreach ($ven as $id=>$v) {
            if (!in_array((int)$v['status'], [1,2,4,77],true)) $this->fail("Unknown schedule status:$id");
            $groups[$find($id)][$id] = $v;
        }
        $activeLegacy = [];
        foreach ($groups as $group) {
            $active = array_filter($group, static fn($v)=>in_array((int)$v['status'],[1,2],true));
            if (!$active) {
                // Approved history must not silently disappear when its terminal row is absent.
                foreach ($group as $id=>$v) if (isset($in[$id]) || isset($out[$id])) $this->fail("No active terminal for chain:$id");
                continue;
            }
            if (count($active) !== 1) { $this->fail('Multiple active schedules in chain:' . array_key_first($group)); continue; }
            $id = array_key_first($active); $v = $active[$id];
            if (isset($out[$id])) $this->fail("Active schedule is not terminal:$id");
            $u = $this->ref('user',$v['user_id']); $s = $this->ref('ven_name_sub',$v['vns_id']); $c = $this->ref('ven_com',$v['ven_com_idb']);
            if (!$u || !$s || !$c) { $this->fail("Missing schedule reference:$id (sub:" . $v['vns_id'] . ')'); continue; }
            if (!self::date($v['ven_date'],'Y-m-d')) $this->fail("Invalid schedule date:$id");
            $newId = $this->allocate('ven_schedule',$id);
            $activeLegacy[$newId] = (string)$id;
            foreach ($group as $oldId=>$_) $this->maps['ven_schedule'][(string)$oldId] = $newId;
            $this->add('ven_schedule',['id'=>$newId, 'ven_date'=>$v['ven_date'], 'ven_com_id'=>$c, 'ven_name_sub_id'=>$s,
                'user_id'=>$u, 'status'=>$v['status'], 'created_at'=>$this->timestamp($v['create_at'],"ven:$id"), 'google_event_id'=>null]);
        }
        $pending = [];
        foreach ($changes as $c) {
            if ((string)$c['status'] === '77') continue;
            $id = (string)$c['id']; $s1 = $this->ref('ven_schedule',$c['ven_id1']);
            $swap = !empty($c['ven_id2']) || !empty($c['ven_id2_old']);
            $s2 = $swap ? $this->ref('ven_schedule',$c['ven_id2']) : null;
            $u1 = $this->ref('user',$c['user_id1']); $u2 = $this->ref('user',$c['user_id2']);
            if (!$s1 || ($swap && !$s2) || !$u1 || !$u2 || $u1 === $u2 || ($swap && $s1 === $s2) || strlen($id)>20) { $this->fail("Invalid transformed change:$id"); continue; }
            if ((int)$c['status'] === 2) {
                foreach ($swap ? [$s1,$s2] : [$s1] as $s) {
                    if (isset($pending[$s])) $this->fail("Multiple pending requests for schedule:$s");
                    $pending[$s] = $id;
                }
                foreach ([1,2] as $side) if (isset($edges[$id][$side])) {
                    $v = $ven[$edges[$id][$side]];
                    if ((int)$v['status'] !== 2 || isset($out[$v['id']])) $this->fail("Pending change is not current:$id");
                }
            }
            $this->add('ven_change',['id'=>$this->allocate('ven_change',$id), 'change_no'=>$id, 's1_id'=>$s1, 's2_id'=>$s2,
                'user1_id'=>$u1, 'user2_id'=>$u2, 'is_swap'=>$swap ? 1 : 0, 'status'=>(int)$c['status'] === 2 ? 0 : 1,
                'created_at'=>$this->timestamp($c['create_at'],"ven_change:$id")]);
        }
        $locked = $config['preserve_locked_pending'] ?? [];
        $used = [];
        foreach ($this->rows['ven_schedule'] ?? [] as $s) if ((int)$s['status'] === 2 && !isset($pending[$s['id']])) {
            $legacyId = $activeLegacy[$s['id']];
            if (is_string($locked[$legacyId] ?? null) && trim($locked[$legacyId]) !== '') {
                $used[$legacyId] = true;
                $this->warnings[] = "Preserved locked legacy pending schedule:$legacyId; no request invented, manual review required.";
            } else $this->fail("Pending schedule has no request:legacy:$legacyId");
        }
        foreach ($locked as $legacyId=>$_) if (!isset($used[(string)$legacyId])) $this->fail("preserve_locked_pending does not match an orphan pending schedule:$legacyId");
    }

    private function options(array $source, array $target): void
    {
        $setting = $target['system_settings'][0] ?? null;
        if (!$setting || (int)$setting['id'] !== 1) { $this->fail('Target system_settings id=1 missing'); return; }
        $options = json_decode($setting['user_options'] ?: '{}',true,512,JSON_THROW_ON_ERROR);
        if (!is_array($options)) { $this->fail('Invalid user_options JSON object'); return; }
        foreach (['fname'=>'prefixes','dep'=>'positions','group'=>'departments'] as $table=>$key) {
            $options[$key] = array_values(array_unique(array_filter(array_merge($options[$key] ?? [], array_map(static fn($r)=>trim($r['name']),$source[$table])))));
        }
        $this->add('system_settings',['id'=>1,'user_options'=>json_encode($options,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
}
