#  🕰️  time-tracker__test
coachtech模擬案件　勤怠管理アプリ
## 📃laravel環境構築
**🐳Dockerビルド**
1. gitのクローン
```bash
git clone git@github.com:RINGO-days/time-tracker__test.git
```
2. Dockerデスクトップアプリを立ち上げる
3. ワーキングディレクトリに移動
```bash
cd free-market-test
```

4. Dockerの立ち上げ
```bash
docker-compose up -d --build
```
<details>
<summary style="cursor: pointer;">⚠️ Mac(Apple Silicon)をお使いの方</summary>

>Apple Silicon搭載のMacでは、docker-conpose up -d実行時に以下のエラーが発生することがあります
>### 🚫症状
>`The requested image's platform (linux/amd64) does not match the detected host platform`
>などの警告が出て、作業が終了する場合がある。
>### 💡対策
>docker-compose.ymlを開き、プラットフォームを明示する。
>```text
>mysql:
>        image: mysql:8.0.26
>        platform: linux/amd64　←この行を追加
>        environment:
>        〜
>```

</details>

## 🌲環境構築
**Dockerを立ち上げた後は、以下の手順を順番に実行してください**
### 1. phpコンテナへログイン
```bash
docker-compose exec php bash
```
### 2. ライブラリのインストール
```bash
composer install
```
### 3. 環境設定ファイルの作成
```bash
cp .env.example .env
```
### 4. アプリケーションキーの作成
```bash
php artisan key:generate
```
### 5. データベースおよび初期データの投入
```bash
php artisan migrate:fresh --seed
```
## 🛠使用技術
- Laravel 8.83.29
- PHP 8.1.34
- mysql 8.0.26
- nginx 1.21.1
- phpMyAdmin
- MailHog
- Fortify
- Sanctum
## 📍開発環境
- http://localhost ホーム画面
- http://localhost/register 新規会員登録画面
- http://localhost/login スタッフログイン画面
- http://localhost/admin/login 管理者ログイン画面
- http://localhost:8080 phpMyAdmin
- http://localhost:8025 MailHog
## 🔑テスト環境
**シーダーファイルには３人分のユーザーが登録されています**

| 名前 | メールアドレス | パスワード | 役割 |
| :-- | :-- | :-- | :-- |
| ユーザー1（一般） | `user1@example.com` | `password` | 過去５ヶ月の15日分の通常勤怠データ(09:00 ~ 18:00 休憩 12:00~13:00)、及び当月のみ通常勤怠が10日、残業(09:00~20:00)3日、遅刻(09:30~18:00)2日、早退(09:00~17:00)1日、長時間労働(09:00~21:00)1日 の17日分のデータがあるユーザー |
| ユーザー2（一般） | `user2@example.com` | `password` | 勤怠が存在しないユーザー |
| ユーザー3（管理者） | `user3@example.com` | `password` | 管理者ユーザー |
## 📃ER図
![ER図](ER.png)
## その他の追加機能
- ### スタッフ用のミドルウェアと管理者用のミドルウェアの作成
> スタッフは管理者用のページにアクセスしようとすると、アクセスできませんの旨のメッセージともに<a>http://localhost/attendance</a>にリダイレクトされる。管理者も同じように一般スタッフ用ページにアクセスしようとすると<a>http://localhost/admin/attendance/list</a>にリダイレクトされる
> #### 作成したミドルウェア　（src/app/Http/Middleware/〜）
> - AdminMiddleware
> - StaffMiddleware
---
- ### 既存の勤怠データではなく、新規で勤怠データを作成するメソッドの作成（過去の休暇申請など）
> 月次勤怠リストに出勤日の時間の記載がない欄の詳細ボタンから、新規で勤怠を作成するできるようにメソッドを追加。
> #### 作成したルート
> - (スタッフ用)Route::get('/attendance/newDetail', [AttendanceDetailController::class, 'newDetail']); //新規勤怠登録画面の表示
> - (管理者用)Route::get('/admin/attendance/newDetail', [AdminController::class, 'newDetailByAdmin']); //新規勤怠登録画面の表示
> - Route::post('/newDetail/propose/staff/{id}', [AttendanceDetailController::class, 'newDetailPropose']); //登録画面から登録するアクション
- ### 修正申請する際に、既存の休憩データの値を消して送信した場合、restsテーブルのレコードを削除する
> 修正申請を行う勤怠詳細画面で、既存の休憩データの入力フィールドの値を休憩開始時間と休憩終了時間を消して送信し、管理者が承認、もしくは直接修正した場合に休憩テーブルの該当のレコードを削除するロジック
> #### 追加したコントローラーアクション
> - AttendanceDetailController proposal //スタッフ、管理者共通、修正を申請するためのアクション。スタッフだったら修正申請、管理者だったら直接updateを行う。管理者の分岐のアクションにこのロジックを追加
> - AdminController　approve //管理者画面の修正申請を承認するアクションに追加
