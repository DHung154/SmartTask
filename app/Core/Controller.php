<?php
namespace App\Core;

class Controller
{
    protected function view($view, $data = [])
    {
        $data['old'] = $data['old'] ?? Session::pullOld();
        $data['errors'] = $data['errors'] ?? Session::pullErrors();
        extract($data);
        $path = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($path)) self::abort("Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y giao di\u{1EC7}n", "View kh\u{00F4}ng t\u{1ED3}n t\u{1EA1}i: {$view}");
        require $path;
    }

    protected function redirect($url) { header("Location: {$url}"); exit; }

    protected function backWithErrors($url, array $errors, $summary = null)
    {
        Session::setErrors($errors);
        Session::setOld($_POST);
        if ($summary === null) {
            $summary = count($errors) === 1 ? reset($errors) : "Vui l\u{00F2}ng ki\u{1EC3}m tra l\u{1EA1}i " . count($errors) . " \u{00F4} nh\u{1EAD}p.";
        }
        Session::flash('error', $summary);
        $this->redirect($url);
    }

    protected function requireAuth() { if (!Session::has('user_id')) $this->redirect('/login'); }
    protected function requireGuest() { if (Session::has('user_id')) $this->redirect('/'); }

    protected function requirePost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            Session::flash('error', "H\u{00E0}nh \u{0111}\u{1ED9}ng n\u{00E0}y ph\u{1EA3}i d\u{00F9}ng POST.");
            $this->redirect('/');
        }
    }

    protected function requireCsrf($redirectTo = '/')
    {
        if (!Csrf::check()) {
            Session::flash('error', "Phi\u{00EA}n l\u{00E0}m vi\u{1EC7}c \u{0111}\u{00E3} h\u{1EBF}t h\u{1EA1}n. Vui l\u{00F2}ng th\u{1EED} l\u{1EA1}i.");
            $this->redirect($redirectTo);
        }
    }

    public static function abort($title, $details = '', $code = 500)
    {
        while (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=UTF-8');
        }
        if ($details !== '') Logger::error('http.abort', ['status' => $code, 'title' => $title, 'details' => $details]);
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeDetails = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
        $detailHtml = Env::bool('APP_DEBUG', false) && $details !== '' ? "<pre>{$safeDetails}</pre>" : '';
        echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . "<title>{$safeTitle}</title><style>"
            . 'body{font-family:Segoe UI,sans-serif;background:#f3f2f1;color:#292827;display:grid;place-items:center;min-height:100vh;margin:0;padding:20px}.box{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:40px;max-width:720px;box-shadow:0 8px 30px #0001;text-align:center}.icon{font-size:44px}h1{font-size:22px}p{color:#605e5c}pre{text-align:left;background:#faf9f8;border:1px solid #edebe9;border-radius:6px;padding:12px;color:#a4262c;overflow:auto;white-space:pre-wrap}a{display:inline-block;background:#7b68ee;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none}</style></head><body><div class="box"><div class="icon">&#9888;&#65039;</div>'
            . "<h1>{$safeTitle}</h1><p>Vui l&#242;ng th&#7917; l&#7841;i. N&#7871;u l&#7895;i v&#7851;n ti&#7871;p di&#7877;n, h&#227;y ki&#7875;m tra c&#7845;u h&#236;nh h&#7879; th&#7889;ng.</p>{$detailHtml}<a href=\"/\">V&#7873; trang ch&#7911;</a></div></body></html>";
        exit;
    }
}
