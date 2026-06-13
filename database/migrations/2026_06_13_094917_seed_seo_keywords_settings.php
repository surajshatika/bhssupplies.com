<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $related = <<<'KEYWORDS'
HVAC supplies
plumbing supplies
AC supplies
Heating & Air Conditioning
Hand & Power Tools
Unilite Tools
Knipex Hand Tools
Excalibur Water Systems
HVAC supplies Mississauga
HVAC supplies Brampton
HVAC supplies Toronto
plumbing supplies Mississauga
plumbing supplies Brampton
plumbing supplies Toronto
AC supplies Mississauga
AC supplies Brampton
AC supplies Toronto
water treatment systems
water filtration systems
water softeners
UV water purification
reverse osmosis systems
water filters Canada
valves and pipe fittings
copper pipe fittings
PEX pipe fittings
black iron pipe fittings
ACR tubing
refrigeration tubing
whole home humidifiers
HVAC air filters
furnace filters
pipe insulation
armaflex pipe insulation
AC capacitors
ventilation fans
air distribution equipment
electrical disconnect box
liquidtite conduit
thermostat wire
inspection lights
work lights
cutting tools Canada
iron filter Canada
UV sterilizer water
mineral tank Canada
control valves Canada
tannin filter
turbidity filter
specialty water filters
water conditioner
scale control systems
HVAC parts Canada
furnace parts Canada
air conditioning parts
heating supplies Canada
cooling supplies Canada
refrigeration supplies
pipe fittings Canada
plumbing fittings
water purification Canada
heating system parts
cooling system parts
HVAC tools and accessories
mechanical contractor supply
plumbing contractor supply
HVAC technician supply
plumbing and HVAC wholesale
buy HVAC supplies online
HVAC supply store Canada
wholesale supplier Ontario
trade account supplier Canada
contractor discount supply
supply house Canada
trade supply store Ontario
water systems Canada
water treatment products Canada
BHS Supplies Trade Account
HVAC Trade Account
plumbing Trade Account
Leave a Review BHS Supplies
KEYWORDS;

    private string $competitor = <<<'KEYWORDS'
hvac supply store mississauga
wholesale hvac supply
one stop hvac shop
hvac superstore
hvac parts near me
plumbing and hvac supply
hvac wholesale distributor
buy hvac supplies online Canada
hvac supply house near me
plumbing supply store near me
water treatment supplier Canada
contractor supply store
hvac materials supplier
commercial hvac parts
residential hvac supply
HVAC parts supplier GTA
plumbing supply house Toronto
HVAC contractor supplies Canada
wholesale plumbing supply Canada
trade HVAC supply
heating cooling supply store
HVAC equipment supplier Ontario
furnace supplier near me
AC unit supplier Canada
plumbing parts supplier
pipe supplier Canada
tool supplier near me
water filter supplier near me
water softener supplier Ontario
industrial supply store Ontario
mechanical supply house
refrigeration parts supplier
contractor supply near me
wholesale tools Canada
trade supply house GTA
HVAC distributor Toronto
plumbing distributor Ontario
buy tools online Canada
KEYWORDS;

    public function up(): void
    {
        $this->upsert('seo_target_keywords', trim($this->related));
        $this->upsert('seo_competitor_keywords', trim($this->competitor));
    }

    public function down(): void
    {
        // Keywords are data — down() intentionally left empty.
    }

    private function upsert(string $type, string $value): void
    {
        $exists = DB::table('business_settings')->where('type', $type)->exists();
        if ($exists) {
            DB::table('business_settings')->where('type', $type)->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('business_settings')->insert(['type' => $type, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
};
