<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The app timezone changed from UTC to Asia/Manila (config/app.php). Laravel stores date-times as
 * wall-clock text in the app timezone, so values written under UTC are moved forward 8 hours to keep
 * meaning the same moment. Manila is UTC+8 all year (no daylight saving), so the offset is fixed.
 * Date-only columns (visit dates, doses, birthdays) are calendar days and are left alone.
 */
return new class extends Migration
{
    private const HOURS = 8;

    public function up(): void
    {
        $this->shift(self::HOURS);
    }

    public function down(): void
    {
        $this->shift(-self::HOURS);
    }

    private function shift(int $hours): void
    {
        foreach ($this->dateTimeColumns() as $table => $columns) {
            DB::table($table)->update(
                collect($columns)->mapWithKeys(fn (string $column) => [$column => DB::raw($this->addHours($column, $hours))])->all()
            );
        }
    }

    /**
     * @return array<string, list<string>> table => its datetime/timestamp columns
     */
    private function dateTimeColumns(): array
    {
        $connection = DB::connection();

        // On MySQL, getTables() lists every database on the server; only this app's may be touched.
        $schema = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            ? $connection->getDatabaseName()
            : null;

        return collect(Schema::getTables())
            ->filter(fn (array $table) => $schema === null || $table['schema'] === $schema)
            ->mapWithKeys(fn (array $table) => [
                $table['name'] => collect(Schema::getColumns($table['name']))
                    ->filter(fn (array $column) => in_array($column['type_name'], ['datetime', 'timestamp'], true))
                    ->pluck('name')
                    ->all(),
            ])
            ->filter()
            ->all();
    }

    private function addHours(string $column, int $hours): string
    {
        $wrapped = DB::connection()->getQueryGrammar()->wrap($column);

        return DB::connection()->getDriverName() === 'sqlite'
            ? sprintf("datetime(%s, '%+d hours')", $wrapped, $hours)
            : sprintf('date_add(%s, interval %d hour)', $wrapped, $hours);
    }
};
