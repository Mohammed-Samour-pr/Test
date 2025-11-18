-- تحديث كلمة مرور المسؤول إلى moh14
-- استخدم هذا الملف إذا كنت قد قمت بالفعل باستيراد قاعدة البيانات

USE media_management;

-- تحديث كلمة المرور للمستخدم admin
UPDATE admins
SET password = '$2y$12$oRFclTivG8yv/eN3s1Ym4Oy0Y/qlCGGLT9joO925vVWoRBEYLhRMy'
WHERE username = 'admin';

-- عرض رسالة تأكيد
SELECT 'تم تحديث كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول بكلمة المرور: moh14' AS message;
