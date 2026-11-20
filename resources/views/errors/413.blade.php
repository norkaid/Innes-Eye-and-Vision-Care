<!DOCTYPE html>
<html lang="en-GH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Upload too large</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-navy-50">
    <main class="mx-auto max-w-xl px-4 py-16">
        <div class="card space-y-4 p-8">
            <h1 class="font-display text-2xl font-bold">The upload was too large</h1>
            <p>The pictures you sent add up to more than the server accepts in one go (currently <strong>{{ ini_get('post_max_size') }}</strong> in total). Nothing was saved.</p>
            <p>Go back and upload fewer pictures at a time, or smaller ones. An administrator can raise <code>post_max_size</code> and <code>upload_max_filesize</code> in PHP's settings (see the warning in <em>Admin → Media library</em>).</p>
            <p><a href="javascript:history.back()" class="btn-primary">Go back</a></p>
        </div>
    </main>
</body>
</html>
