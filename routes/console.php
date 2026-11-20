<?php
use App\Models\{User, Product};
use Illuminate\Support\Facades\{Artisan, DB};

Artisan::command('soko:cafe-upgrade', function () {
    DB::transaction(function () {
        $original = User::where('username', 'buen')->lockForUpdate()->first();
        if ($original) {
            if (User::withTrashed()->where('username', 'group4')->exists()) {
                throw new \RuntimeException('The username group4 already exists. Resolve this account conflict before retrying. No changes were applied.');
            }
            $original->update(['username'=>'group4', 'full_name'=>'Group 4']);
        }
        // Archive only the original demo items. Keep their IDs and all sale history.
        $originals = [
            'Sandstone ceramic mug'=>'demo-mug.svg', 'Everyday canvas tote'=>'demo-tote.svg',
            'Cedar & sage candle'=>'demo-candle.svg', 'Studio dotted notebook'=>'demo-notebook.svg',
            'Olive steel bottle'=>'demo-bottle.svg', 'Terracotta desk planter'=>'demo-planter.svg',
        ];
        foreach ($originals as $name=>$image) {
            Product::where('name',$name)->where('image',$image)->update(['is_archived'=>true]);
        }
        foreach (config('cafe.menu') as $item) {
            Product::firstOrCreate(['name'=>$item['name']], $item);
        }
    });
    $this->info('Cafe update applied. Original demo products are archived; coffee and pastry items are available.');
    $this->info('The original buen login is now group4 with the SAME password. Existing receipts remain unchanged.');
})->purpose('Apply the Group 4 cafe menu and rename the original staff login without resetting data');
