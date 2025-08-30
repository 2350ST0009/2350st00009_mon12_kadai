<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (isset($_POST['body'])) {
    $image_filename = null;

    if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
        if (preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
            header("HTTP/1.1 302 Found");
            header("Location: ./bbsimagetest.php");
            return;
        }
        $pathinfo = pathinfo($_FILES['image']['name']);
        $extension = $pathinfo['extension'];
        $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
        $filepath = '/var/www/upload/image/' . $image_filename;
        move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
    }

    $reply_to = (isset($_POST['reply_to']) && $_POST['reply_to'] !== '') ? (int)$_POST['reply_to'] : null;

    $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename, reply_to) VALUES (:body, :image_filename, :reply_to)");
    $insert_sth->execute([
        ':body' => $_POST['body'],
        ':image_filename' => $image_filename,
        ':reply_to' => $reply_to
    ]);

    header("HTTP/1.1 302 Found");
    header("Location: ./bbsimagetest.php");
    return;
}

$sth = $dbh->prepare('SELECT id, body, created_at, image_filename, reply_to FROM bbs_entries ORDER BY created_at DESC');
$sth->execute();
$entries = $sth->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>掲示板</title>
<style>
body {
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
  padding: 1em;
  background-color: #f4f4f9;
  color: #333;
  line-height: 1.5;
  max-width: 800px;
  margin: 0 auto;
}
form {
  background-color: #fff;
  padding: 1em;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 2em;
}
textarea {
  width: 100%;
  box-sizing: border-box;
  min-height: 5em;
  margin-bottom: 0.5em;
  padding: 0.5em;
  border-radius: 5px;
  border: 1px solid #ccc;
  font-size: 16px;
  resize: vertical;
}
button {
  padding: 0.6em 1.2em;
  border: none;
  border-radius: 5px;
  background-color: #4CAF50;
  color: white;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
}
button:hover {
  background-color: #45a049;
}
img {
  max-width: 100%;
  height: auto;
  display: block;
  margin-top: 0.5em;
  border-radius: 5px;
}
dl {
  background: #fff;
  padding: 1em;
  border-radius: 10px;
  margin-bottom: 1em;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  word-break: break-word;
}
dt {
  font-weight: bold;
  margin-top: 0.5em;
}
dd {
  margin: 0 0 0.5em 0;
}
.replyLink {
  display: inline-block;
  margin-top: 0.5em;
  font-size: 14px;
  color: #007BFF;
  text-decoration: none;
}
.replyLink:hover {
  text-decoration: underline;
}
#replyInfo {
  margin-bottom: 0.5em;
  font-size: 14px;
  color: #555;
}
.replyToPost {
  font-size: 13px;
  color: #666;
  margin-bottom: 0.3em;
}
.replyToPost a {
  color: #007BFF;
  text-decoration: none;
}
.replyToPost a:hover {
  text-decoration: underline;
}
@media (max-width: 600px) {
  body { padding: 0.5em; }
  textarea { font-size: 14px; }
  button { width: 100%; font-size: 14px; }
  dl { padding: 0.8em; font-size: 14px; }
  .replyLink { font-size: 12px; }
  #replyInfo { font-size: 12px; }
  .replyToPost { font-size: 12px; }
}
</style>
</head>
<body>

<form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data" id="bbsForm">
  <input type="hidden" name="reply_to" id="replyTo">
  <div id="replyInfo"></div>
  <textarea name="body" required placeholder="本文を入力"></textarea>
  <div>
    <input type="file" accept="image/*" name="image" id="imageInput">
  </div>
  <button type="submit">送信</button>
</form>

<hr>

<?php foreach($entries as $entry): ?>
  <dl id="post-<?= $entry['id'] ?>">
    <dt>
      <a href="#post-<?= $entry['id'] ?>" class="scrollLink">No.<?= $entry['id'] ?></a>
    </dt>
    <dd><?= $entry['created_at'] ?></dd>
    <dt>内容</dt>
    <dd>
      <?php if (!empty($entry['reply_to'])): ?>
        <div class="replyToPost">返信先: <a href="#post-<?= htmlspecialchars($entry['reply_to']) ?>" class="scrollLink">No.<?= htmlspecialchars($entry['reply_to']) ?></a></div>
      <?php endif; ?>
      <?= nl2br(htmlspecialchars($entry['body'])) ?>
      <?php if(!empty($entry['image_filename'])): ?>
        <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>">
      <?php endif; ?>
      <a href="#bbsForm" class="replyLink" data-id="<?= $entry['id'] ?>">#この投稿に返信</a>
    </dd>
  </dl>
<?php endforeach ?>

<script>
const imageInput = document.getElementById("imageInput");
const MAX_FILE_SIZE = 1 * 1024 * 1024;

imageInput.addEventListener("change", () => {
  const file = imageInput.files[0];
  if (!file) return;
  if (file.size <= MAX_FILE_SIZE) return;

  const reader = new FileReader();
  reader.onload = e => {
    const img = new Image();
    img.onload = () => {
      let scale = Math.sqrt(MAX_FILE_SIZE / file.size);
      const canvas = document.createElement("canvas");
      canvas.width = img.width * scale;
      canvas.height = img.height * scale;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

      function compress(blob, quality = 0.9) {
        if (blob.size <= MAX_FILE_SIZE) return blob;
        scale *= Math.sqrt(MAX_FILE_SIZE / blob.size);
        canvas.width = img.width * scale;
        canvas.height = img.height * scale;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        return new Promise(resolve => {
          canvas.toBlob(newBlob => resolve(compress(newBlob, quality * 0.9)), "image/jpeg", quality);
        });
      }

      canvas.toBlob(async initialBlob => {
        const finalBlob = await compress(initialBlob);
        const newFile = new File([finalBlob], file.name, { type: "image/jpeg" });
        const dt = new DataTransfer();
        dt.items.add(newFile);
        imageInput.files = dt.files;
      }, "image/jpeg", 0.9);
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
});

const replyInfo = document.getElementById('replyInfo');
document.querySelectorAll('.replyLink').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const id = link.dataset.id;
    document.getElementById('replyTo').value = id;
    replyInfo.textContent = `返信先: No.${id}`;
    document.getElementById('bbsForm').scrollIntoView({ behavior: 'smooth' });
  });
});

document.querySelectorAll('.scrollLink').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const target = document.querySelector(link.getAttribute('href'));
    if (target) {
      target.scrollIntoView({ behavior: 'smooth' });
    }
  });
});
</script>

</body>
</html>

