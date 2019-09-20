<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Contact;
use App\Item;
use App\Payment;
use App\Invoice;
use App\Opportunity;
use DB;
use Illuminate\Support\Arr; // laravelのver6〜はarray_add()は使えなくなった→Arr::add()

class DashboardController extends Controller
{
    public function index()
    {
    	$cards = [
    		[
                // コンタクトを全件取得
    			'title' => 'All Contacts',
    			'type' => 'value',
    			'value' => Contact::count()
    		],
    		[
                // アイテムを全件取得
    			'title' => 'All Items',
    			'type' => 'value',
    			'value' => Item::count()
    		],
    		[
                // Invoice（請求書）のsentのものを取得
    			'title' => 'Un Paid Invoices',
    			'type' => 'value',
    			'value' => Invoice::where('status', 'sent')->count()
    		],
    		[
    			'title' => 'New Opportunities',
    			'type' => 'value',
    			'value' => Opportunity::where('status', 'new')->count()
    		],
    		[
    			'title' => 'Opportunities Lost',
    			'type' => 'chart',
    			'color' => '#6be6c1',
    			'value' => $this->getChart(Opportunity::where('status', 'lost'), 'created_at')
    		],
    		[
    			'title' => 'Opportunities Won',
    			'type' => 'chart',
    			'color' => '#96dee8',
    			'value' => $this->getChart(Opportunity::where('status', 'won'), 'created_at')
    		],
    		[
    			'title' => 'Paid Invoices',
    			'type' => 'chart',
    			'color' => '#6be6c1',
    			'value' => $this->getChart(Invoice::where('status', 'paid'), 'issue_date')
    		],
    		[
    			'title' => 'Deposited Payments',
    			'type' => 'chart',
    			'color' => '#6be6c1',
    			'value' => $this->getChart(Payment::where('status', 'deposited'), 'payment_date')
    		],
    		[
    			'title' => 'Undeposited Funds',
    			'type' => 'value',
    			'value' => Payment::where('status', 'undeposited')->count()
    		]
        ];
    	return response()
    		->json(['cards' => $cards]);
    }


    public function getChart($model, $column)
    {
        // MySQLの生文、上の$cards配列で決めたvalueの$columnを何日か（%d）というフォーマットに合わせたものを変数に代入
        $valueFormat = DB::raw("DATE_FORMAT(".$column.", '%d') as value");
        // now関数は、現時点を表す新しいIlluminate\Support\Carbonインスタンスを生成し、startOfMonthで月の初めが取れる
        // 2019年9月26日の場合→2019-09-01が取れる
        $start = now()->startOfMonth();
        // 逆で→2019-09-31が取れる
        $end = now()->endOfMonth();

        $dates = [];

        $run = $start->copy();

        // $runと$endは同じフィールドで、なおかつ『以下』であるかを確認。1日〜31日まで間ループする
    	while($run->lte($end)) {
            // $dates = array_add($dates, $run->copy()->format('d'), 0);
            // laravelのver6〜はarray_add()は使えなくなった→Arr::add()
            // もし配列$datesにフィールド$run->copy()->format('d')の項目がなかったら、最後にフィールド日付（format('d')）に0を追加
            $dates = Arr::add($dates, $run->copy()->format('d'), 0);
            // startOfMonth()に次の日、1日追加する
            $run->addDay(1);
        }

        // $modelの中の？$columnを取得
        $res = $model->groupBy($column)
            // 'count(*) as total'と$valueFormatが一致するもの
            ->select(DB::raw('count(*) as total'), $valueFormat)
            //
            ->pluck('total', 'value');
            // dd($model);
        $all = $res->toArray() + $dates;
        // dd($all);

        // 配列のキーを軸に昇順で並び替えます。
        ksort($all);
        // dd($all);
    	return collect(array_values($all))->map(function($item) {
    		return ['value' => $item];
    	});
    }
}
