<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isInstitutionLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();
$institution_id = $_SESSION['institution_id'];
$album_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get album info and verify it belongs to this institution
$stmt = $conn->prepare("
    SELECT a.*, i.name as institution_name
    FROM albums a
    JOIN institutions i ON a.institution_id = i.id
    WHERE a.id = ? AND a.institution_id = ? AND a.is_active = 1
");
$stmt->bind_param("ii", $album_id, $institution_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('gallery.php');
}

$album = $result->fetch_assoc();
$stmt->close();

// Get all media for this album
$stmt = $conn->prepare("
    SELECT * FROM media
    WHERE album_id = ?
    ORDER BY display_order ASC, created_at DESC
");
$stmt->bind_param("i", $album_id);
$stmt->execute();
$media_files = $stmt->get_result();
$stmt->close();

// Count media types
$images_count = 0;
$videos_count = 0;
$media_files->data_seek(0);
while ($media = $media_files->fetch_assoc()) {
    if ($media['file_type'] == 'image') {
        $images_count++;
    } else {
        $videos_count++;
    }
}
$media_files->data_seek(0);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $album['title']; ?> - <?php echo $album['institution_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .album-header {
            background: linear-gradient(135deg, var(--success) 0%, #47be7d 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.625rem;
        }
        .album-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .back-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }
        .logout-btn {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
        }
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .lightbox.active {
            display: flex;
        }
        .lightbox-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1.5rem;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: none;
            padding: 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 2rem;
        }
        .lightbox-prev {
            right: 1rem;
        }
        .lightbox-next {
            left: 1rem;
        }
    </style>
</head>
<body>
    <a href="gallery.php" class="btn btn-light back-btn">← رجوع</a>
    <a href="logout.php" class="btn btn-danger logout-btn">🚪 تسجيل الخروج</a>

    <div class="container" style="padding: 2rem 15px;">
        <div class="album-header">
            <h1>📁 <?php echo $album['title']; ?></h1>
            <?php if ($album['description']): ?>
                <p style="opacity: 0.9;"><?php echo $album['description']; ?></p>
            <?php endif; ?>
            <div style="margin-top: 1rem; display: flex; gap: 1rem; font-size: 0.9rem;">
                <span>🖼️ <?php echo $images_count; ?> صورة</span>
                <span>🎥 <?php echo $videos_count; ?> فيديو</span>
                <span>📊 <?php echo $images_count + $videos_count; ?> إجمالي</span>
            </div>
        </div>

        <?php if ($media_files->num_rows > 0): ?>
            <div class="media-grid">
                <?php $index = 0; while ($media = $media_files->fetch_assoc()): ?>
                    <div class="media-item" onclick="openLightbox(<?php echo $index; ?>)" style="cursor: pointer;">
                        <?php if ($media['file_type'] == 'image'): ?>
                            <img src="../<?php echo $media['file_path']; ?>" alt="<?php echo $media['title']; ?>" data-index="<?php echo $index; ?>">
                        <?php else: ?>
                            <video src="../<?php echo $media['file_path']; ?>" data-index="<?php echo $index; ?>"></video>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 3rem; opacity: 0.8;">▶️</div>
                        <?php endif; ?>

                        <?php if ($media['title']): ?>
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: white; padding: 1rem 0.5rem 0.5rem; font-size: 0.875rem;">
                                <?php echo $media['title']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php $index++; endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="padding: 4rem 2rem; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                    <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">الألبوم فارغ</h3>
                    <p style="color: var(--gray-500);">لا توجد ملفات في هذا الألبوم</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()">✕</button>
        <button class="lightbox-nav lightbox-prev" onclick="prevMedia()">→</button>
        <button class="lightbox-nav lightbox-next" onclick="nextMedia()">←</button>
        <img id="lightbox-img" class="lightbox-content" style="display: none;">
        <video id="lightbox-video" class="lightbox-content" controls style="display: none;"></video>
    </div>

    <script>
        const mediaData = [
            <?php
            $media_files->data_seek(0);
            $items = [];
            while ($media = $media_files->fetch_assoc()) {
                $items[] = json_encode([
                    'type' => $media['file_type'],
                    'path' => '../' . $media['file_path'],
                    'title' => $media['title']
                ]);
            }
            echo implode(',', $items);
            ?>
        ];

        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            showMedia();
            document.getElementById('lightbox').classList.add('active');
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            const video = document.getElementById('lightbox-video');
            video.pause();
        }

        function nextMedia() {
            currentIndex = (currentIndex + 1) % mediaData.length;
            showMedia();
        }

        function prevMedia() {
            currentIndex = (currentIndex - 1 + mediaData.length) % mediaData.length;
            showMedia();
        }

        function showMedia() {
            const media = mediaData[currentIndex];
            const img = document.getElementById('lightbox-img');
            const video = document.getElementById('lightbox-video');

            if (media.type === 'image') {
                img.src = media.path;
                img.style.display = 'block';
                video.style.display = 'none';
                video.pause();
            } else {
                video.src = media.path;
                video.style.display = 'block';
                img.style.display = 'none';
                video.play();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    nextMedia();
                } else if (e.key === 'ArrowRight') {
                    prevMedia();
                }
            }
        });

        // Close on background click
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
    </script>
</body>
</html>
<?php closeConnection($conn); ?>
