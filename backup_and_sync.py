# Auto Backup & GitHub Sync for GhorbaniKids
import json
import base64
import zlib
import subprocess
import os
import sys
import datetime
import urllib.request
import ssl
import zipfile
import io

if sys.platform == 'win32':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
        sys.stderr.reconfigure(encoding='utf-8')
    except Exception:
        pass

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

auth = base64.b64encode(b"admin:Ln7MbhMNt6Efo3w8k7NZ5YLp").decode()
WORK_DIR = "d:\\work\\ghorbanikids"

def req(payload, session_id=None):
    headers = {
        "Authorization": "Basic " + auth,
        "Content-Type": "application/json",
        "User-Agent": "python-backup"
    }
    if session_id:
        headers["Mcp-Session-Id"] = session_id
    r = urllib.request.Request("https://ghorbanikids.ir/?rest_route=/mcp/novamira", data=json.dumps(payload).encode(), headers=headers)
    with urllib.request.urlopen(r, context=ctx) as resp:
        sid = resp.headers.get("mcp-session-id")
        return json.loads(resp.read().decode()), sid

def run_backup(commit_msg=None):
    print("=== [GK] Starting Full GhorbaniKids Backup & GitHub Sync ===")
    init_res, sid = req({
        "jsonrpc": "2.0",
        "id": 1,
        "method": "initialize",
        "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "gk-backup", "version": "1.0.0"}}
    })
    print("[1/4] Connected to live server.")
    
    php_code = """
    global $wpdb;
    $tables = $wpdb->get_col('SHOW TABLES');
    $sql_file = '/tmp/gk_backup_dump.sql';
    $handle = fopen($sql_file, 'w');
    fwrite($handle, "-- GhorbaniKids Database Backup\\n-- Date: " . date('Y-m-d H:i:s') . "\\n\\nSET NAMES utf8mb4;\\nSET FOREIGN_KEY_CHECKS = 0;\\n\\n");
    foreach ($tables as $table) {
        $create = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\\n" . $create[1] . ";\\n\\n");
        $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        if (!empty($rows)) {
            foreach (array_chunk($rows, 100) as $chunk) {
                $inserts = [];
                foreach ($chunk as $row) {
                    $vals = array_map(function($v) use ($wpdb) {
                        return is_null($v) ? 'NULL' : "'" . esc_sql($v) . "'";
                    }, array_values($row));
                    $inserts[] = '(' . implode(', ', $vals) . ')';
                }
                $cols = array_map(function($c) { return "`$c`"; }, array_keys($chunk[0]));
                fwrite($handle, "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES\\n" . implode(",\\n", $inserts) . ";\\n");
            }
            fwrite($handle, "\\n");
        }
    }
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\\n");
    fclose($handle);
    $sql_content = file_get_contents($sql_file);
    @unlink($sql_file);

    $zip_file = '/tmp/gk_code_sync.zip';
    if (file_exists($zip_file)) @unlink($zip_file);
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $mu = WP_CONTENT_DIR . '/mu-plugins';
        $r1 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mu));
        foreach ($r1 as $f) { if (!$f->isDir()) $zip->addFile($f->getPathname(), 'mu-plugins/' . str_replace($mu . '/', '', $f->getPathname())); }
        
        $th = get_stylesheet_directory();
        $r2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($th));
        foreach ($r2 as $f) { if (!$f->isDir()) $zip->addFile($f->getPathname(), 'themes/ghorbanikids/' . str_replace($th . '/', '', $f->getPathname())); }

        $gm = WP_CONTENT_DIR . '/games';
        if (is_dir($gm)) {
            $r3 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($gm));
            foreach ($r3 as $f) { if (!$f->isDir()) $zip->addFile($f->getPathname(), 'games/' . str_replace($gm . '/', '', $f->getPathname())); }
        }
        $zip->close();
    }
    $zip_data = file_get_contents($zip_file);
    @unlink($zip_file);

    echo json_encode([
        'sql_gz' => base64_encode(gzencode($sql_content, 9)),
        'code_zip' => base64_encode($zip_data)
    ]);
    """
    
    print("[2/4] Exporting Database and Codebase from server...")
    res2, _ = req({
        "jsonrpc": "2.0",
        "id": 2,
        "method": "tools/call",
        "params": {
            "name": "mcp-adapter-execute-ability",
            "arguments": {"ability_name": "novamira/execute-php", "parameters": {"code": php_code}}
        }
    }, sid)
    
    out = json.loads(json.loads(res2["result"]["content"][0]["text"])["data"]["output"])
    
    gz_bytes = base64.b64decode(out["sql_gz"])
    with open(os.path.join(WORK_DIR, 'database_backup.sql.gz'), 'wb') as f:
        f.write(gz_bytes)
    sql_decompressed = zlib.decompress(gz_bytes, 16 + zlib.MAX_WBITS)
    with open(os.path.join(WORK_DIR, 'database_backup.sql'), 'wb') as f:
        f.write(sql_decompressed)
    print(f"[3/4] Database dump saved ({len(sql_decompressed) / 1024 / 1024:.2f} MB).")
    
    code_bytes = base64.b64decode(out["code_zip"])
    with zipfile.ZipFile(io.BytesIO(code_bytes)) as z:
        z.extractall(os.path.join(WORK_DIR, 'wp-content'))
    print("[3/4] Codebase synced into wp-content/ (themes, mu-plugins, games).")
    
    now_str = datetime.datetime.now().strftime('%Y-%m-%d %H:%M')
    final_msg = commit_msg or f"chore(backup): automated full backup and sync ({now_str})"
    
    print(f"[4/4] Committing to Git: {final_msg}")
    subprocess.run(["git", "add", "."], cwd=WORK_DIR, check=True)
    try:
        subprocess.run(["git", "commit", "-m", final_msg], cwd=WORK_DIR, check=True)
        print("[4/4] Git commit created.")
    except subprocess.CalledProcessError:
        print("[4/4] No new changes detected since last commit.")
        
    print("[4/4] Pushing to GitHub (main)...")
    subprocess.run(["git", "push", "origin", "main"], cwd=WORK_DIR, check=True)
    print("=== [SUCCESS] Full Backup & GitHub Sync Completed! ===")

if __name__ == '__main__':
    msg = ' '.join(sys.argv[1:]) if len(sys.argv) > 1 else None
    run_backup(msg)
