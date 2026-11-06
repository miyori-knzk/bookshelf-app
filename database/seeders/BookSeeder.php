<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * 書籍 11 件を登録する。登録者は最初のユーザー（山田太郎）。
     * ISBN が同じ書籍は作り直さず、ジャンルの紐付けは同期する。
     */
    public function run(): void
    {
        $user = User::first();

        collect([
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'genres' => ['小説'],
                'description' => '猫の視点から、明治の人間模様を風刺的に描いた夏目漱石のデビュー作。',
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '人に好かれ、人を動かすための原則を、具体的な事例で紹介する自己啓発の古典。',
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'genres' => ['技術書'],
                'description' => '読みやすく保守しやすいコードを書くための、実践的なテクニックをまとめた一冊。',
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '人格主義に基づく、成功するための 7 つの習慣を説く世界的ベストセラー。',
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'genres' => ['小説'],
                'description' => '無鉄砲な教師「坊っちゃん」が、松山の中学校で巻き起こす痛快な青春小説。',
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'genres' => ['歴史', '科学'],
                'description' => '認知革命・農業革命・科学革命を軸に、人類の歴史を大きな視点で読み解く。',
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'genres' => ['技術書'],
                'description' => '良いコードと悪いコードの違いを、具体例とリファクタリングで学ぶ。',
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'genres' => ['自己啓発'],
                'description' => 'アドラー心理学を、青年と哲人の対話形式でわかりやすく解説する。',
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'genres' => ['小説'],
                'description' => '売れない漫才師の苦悩と成長を描いた、芥川賞受賞の中編小説。',
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'genres' => ['ビジネス', '科学'],
                'description' => 'データと事実に基づいて世界を正しく見るための、10 の思い込みを解説する。',
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'genres' => ['ビジネス', '歴史'],
                'description' => '一つの箱が世界の物流と経済をどう変えたかを描く、コンテナ誕生の物語。',
            ],
        ])->each(function (array $data, int $index) use ($user): void {
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'published_date' => $data['published_date'],
                    'description' => $data['description'],
                    'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=' . ($index + 1),
                ],
            );

            $book->genres()->sync(Genre::whereIn('name', $data['genres'])->pluck('id'));
        });
    }
}
