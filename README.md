주요 디렉토리와 파일들에 대한 권한을 설정해야 합니다.
chmod 777 kr-zip-server/logs
chmod 777 kr-zip-server/conf/versionDate.inc
chmod 777 kr-zip-server/admin/files
chmod 777 kr-zip-server/admin/cache

kr-zip-server/conf/db.config.php 에 관리자 접속에 사용할 __KRZIP_ADMIN_ID__, __KRZIP_ADMIN_PW__ 를 설정합니다.
http://yourdomain/admin/index.php 에 접속하여 db.config.php 에서 설정한 id, password로 로그인하시고
http://www.epost.go.kr/ 에서 우편번호 파일을 다운받아 압축을 푼 후 서버로 업로드 합니다.


