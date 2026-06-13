<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $related = <<<'KEYWORDS'
HVAC supplies
plumbing supplies
AC supplies
heating and air conditioning supplies
Heating & Air Conditioning
Hand & Power Tools
Unilite Tools
Knipex Hand Tools
Excalibur Water Systems
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
AC capacitors
ventilation fans
air distribution equipment
HVAC tools and accessories
contractor tools Canada
plumbing and HVAC wholesale
buy HVAC supplies online
HVAC supply store Canada
industrial valves supplier
water systems Canada
heating supplies Canada
cooling supplies Canada
thermostat wire
refrigeration supplies
mechanical supplies
wholesale building supplies Canada
commercial plumbing supply
pipe fittings Canada
plumbing fittings
water purification Canada
water conditioner
scale control systems
inspection lights
work lights
site lights Canada
safety supplies Canada
safety gloves and glasses
cutting tools Canada
HVAC parts Canada
air conditioning parts
furnace parts Canada
heating system parts
cooling system parts
trade account supplier Canada
contractor discount supply
wholesale supplier Ontario
mechanical contractor supply
plumbing contractor supply
HVAC technician supply
electrical disconnect box
liquidtite conduit
armaflex pipe insulation
water treatment products Canada
iron filter Canada
UV sterilizer water
mineral tank Canada
control valves Canada
tannin filter
turbidity filter
specialty water filters
trade supply store Ontario
supply house Canada
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
safety equipment supplier Canada
water filter supplier near me
water softener supplier Ontario
industrial supply store Ontario
mechanical supply house
refrigeration parts supplier
building supply store near me
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
