 <?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'scan_date',
        'status'
    ];

    protected $casts = [
        'scan_date' => 'datetime',
        'status' => 'string'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

?>
