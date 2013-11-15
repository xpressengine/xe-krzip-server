## krzip-server

http://domain/server.php?

## 권한 설정

주요 디렉토리와 파일들에 대해서 php 에서 파일을 쓰거나 변경 할 수 있도록 권한을 설정해야 합니다.

+ krzip-server/logs
+ krzip-server/conf/versionDate.inc
+ krzip-server/files
+ krzip-server/cache

## 계정 설정

krzip-server/conf/db.config.php 파일을 참고 하여 krzip-server/conf/db.config.user.php 파일을 작성


## 데이터 생성

+ 다운로드 후 사용자는 "[우체국](http://www.epost.go.kr/)" 홈페이지에서 도로명 주소를 다운 받아야 합니다.

- 각 지역별 모든 엑셀 파일을 모두 다운로드 받아야 함 

+ 다운로드 받은 파일의 압축해제 후 krzip-server/files 폴더로 업로드 합니다.

- files 폴더안에는 반드시 txt 파일만 업로드 되어야 합니다. files 폴더안에 있는 폴더는 처리되지 않습니다. 

+ 업로드 완료 후 krzip-server/bin/krzip-insert-cli.php 를 실행 시킵니다. 

php -f krzip-insert-cli.php


