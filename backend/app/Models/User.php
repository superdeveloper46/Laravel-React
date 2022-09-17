<?php

namespace App\Models;

use App\Http\Controllers\Api\LeadReplyController;
use App\Http\Controllers\Api\TwilioController;
use App\Repositories\UserRepositoryTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

/**
 * App\Models\User
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, UserRepositoryTrait;

    protected $table = 'users';
    
    public static $ROLE_ADMIN = 'ADMIN';
    public static $ROLE_AGENCY = 'AGENCY';
    public static $ROLE_COMPANY = 'COMPANY';
    public static $ROLE_AGENT = 'AGENT';

    public static $SUBSCRIPTION_TYPE_BASE = 'BASE';
    public static $SUBSCRIPTION_TYPE_PREMIUM = 'PREMIUM';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'avatar_id',
        'agent_agency_id',
        'email',
        'password',
        'role',
        'max_agency_companies',
        'subscription_type',
        'company_name',
        'uuid',
        'twilio_sid',
        'twilio_token',
        'twilio_mobile_number',
        'twilio_app_sid',
    ];

    protected $appends = ['avatar_path', 'permissions'];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'avatar',
    ];
    
    public function getDefaultPermissions() {
        return self::getDefaultPermissionsByRole($this->role);
    }

    public static function getDefaultPermissionsByRole($role) {
        if ($role === self::$ROLE_ADMIN) {
            return [
                Permission::$PERMISSION_ALL,
            ];
        }
        elseif ($role === self::$ROLE_AGENCY) {
            return [
                Permission::$PERMISSION_COMPANY_READ,
                Permission::$PERMISSION_COMPANY_WRITE,
                Permission::$PERMISSION_DEAL_READ,
                Permission::$PERMISSION_DEAL_WRITE,
                Permission::$PERMISSION_AGENT_READ,
                Permission::$PERMISSION_AGENT_WRITE,
                Permission::$PERMISSION_LEAD_READ,
                Permission::$PERMISSION_CAMPAIGN_READ,
                Permission::$PERMISSION_DEAL_ACTION_RED,
                Permission::$PERMISSION_DEAL_ACTION_WRITE,
            ];
        } elseif ($role === self::$ROLE_COMPANY) {
            return [
                Permission::$PERMISSION_DEAL_READ,
                Permission::$PERMISSION_AGENT_READ,
                Permission::$PERMISSION_LEAD_READ,
                Permission::$PERMISSION_LEAD_WRITE,
                Permission::$PERMISSION_CAMPAIGN_READ,
                Permission::$PERMISSION_CAMPAIGN_WRITE,
                Permission::$PERMISSION_LEAD_NOTE_READ,
                Permission::$PERMISSION_LEAD_NOTE_WRITE,
                Permission::$PERMISSION_DEAL_ACTION_RED,
                Permission::$PERMISSION_DEAL_ACTION_WRITE,
            ];
        } elseif ($role === self::$ROLE_AGENT) {
            return [
                Permission::$PERMISSION_DEAL_READ,
                Permission::$PERMISSION_LEAD_READ,
                Permission::$PERMISSION_LEAD_WRITE,
                Permission::$PERMISSION_LEAD_NOTE_READ,
                Permission::$PERMISSION_LEAD_NOTE_WRITE,
                Permission::$PERMISSION_CAMPAIGN_READ,
                Permission::$PERMISSION_DEVICE_READ,
                Permission::$PERMISSION_DEVICE_WRITE,
            ];
        }
        return [
            Permission::$PERMISSION_NONE,
        ];
    }
    
    public function permissions() {
        return $this->belongsToMany('App\Models\Permission', 'user_permissions', 'user_id');
    }

    public function reports() {
        return $this->hasMany('App\Models\Reports', 'user_id');
    }
    
    public function avatar() {
        return $this->hasOne('App\Models\Media', 'id', 'avatar_id');
    }
    
    public function companyAgencies() {
        return $this->belongsToMany('App\Models\Agency', 'agency_companies', 'company_id')->withPivot('id', 'is_locked');
    }

    public function getAgencies() {
        return $this->companyAgencies()->where('is_locked',  0)->get()->map(function ($agency) {
            $agency->agency_company_id = $agency->pivot->id;
            $agency->is_locked = $agency->pivot->is_locked;
            
            return $agency->only('id', 'name', 'email', 'avatar_path', 'agency_company_id', 'is_locked');
        });
    }

    public function createUser($data) {
        \Validator::validate($data, self::requiredFieldsForCreate());
        $data['password'] = bcrypt($data['password']);
        $data['uuid'] = Str::uuid();
        $this->fill($data);
        $this->saveOrFail();
        $this->addDefaultPermissions();
        return $this;
    }
    
    /**
     * Uploads the users avatar.
     *
     * @param \Illuminate\Http\Request $request
     *
     * return void
     */
    public function handleAvatar(Request $request)
    {
        $media = new Media;
        if ($media->upload($request, 'avatar', Media::AVATAR_PATH)) {
            $request->merge(['avatar_id' => $media->id]);
        }
    }

    public function updateUser($data) {
        if (isset($data['password'])) {
            \Validator::validate($data, [
                'password' => 'required|confirmed|min:6'
            ]);
            $data['password'] = bcrypt($data['password']);
        }
    
        if (isset($data['email'])) {
            \Validator::validate($data, [
                'email' => "required|email|userEmail:{$this->id}"
            ]);
        }
        $data['uuid'] = ($this->uuid ? $this->uuid : Str::uuid());
        $this->fill($data);
        $this->save();
        return $this;
    }
    
    static function requiredFieldsForCreate() {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|userEmail',
            'password' => 'required|confirmed|min:6',
        ];
    }
    
    public function getPermissionsAttribute() {
        return $this->permissions()->get();
    }
    
    /**
     * Returns user avatar or placeholder path.
     *
     * @return string
     */
    public function getAvatarPathAttribute()
    {
        return $this->avatar ? $this->avatar->path : asset('images/user.png');
    }
    
    public function getPermissions() {
        $userScopes = collect($this->permissions)->map(function ($permission) {
            return $permission->name;
        });
    
        return $userScopes->merge(User::getDefaultPermissionsByRole($this->role))->unique();
    }
    
    public function setupUserRolePermissions() {
        Passport::actingAs($this, $this->getPermissions());
    }

    public static function getAll($query) {
        $user = User::select();

        if (isset($query['search']) && $query['search']) {
            $user
                ->where('name', 'like', "%{$query['search']}%")
                ->orWhere('email', 'like', "%{$query['search']}%")
                ->orWhere('phone', 'like', "%{$query['search']}%")
                ->orWhere('role', 'like', "%{$query['search']}%")
            ;
        }

        if (isset($query['showDeleted']) && $query['showDeleted'] === 'true') {
            $user->withTrashed();
        }

        return $user;
    }

    /**
     * Add default permissions
     */
    public function addDefaultPermissions()
    {
        $permissions = \App\Models\Permission::whereIn('name', $this->getDefaultPermissions())->get();
        $this->permissions()->attach($permissions);
    }

    public function isAgency() {
        return $this->role === self::$ROLE_AGENCY;
    }

    public function isAgent() {
        return $this->role === self::$ROLE_AGENT;
    }

    public function isCompany() {
        return $this->role === self::$ROLE_COMPANY;
    }

    public function setupTwilioSmsWebHook($twilioSid, $twilioToken, $twilioNumber) {
        if (!trim($twilioSid) || !trim($twilioToken) || !trim($twilioNumber)) {
            return;
        }
        
        // PN54b75d243356513ec9811b4e3992cf17
        $twilioClient = new Client($twilioSid, $twilioToken); 

        $result = $twilioClient->incomingPhoneNumbers->read([
            'phoneNumber' => $twilioNumber,
        ], 1);
        $number = last($result);

        if (empty($number)) {
            abort('Not valid number');
        }

        if ($number->sid) {
            $twilioClient->incomingPhoneNumbers($number->sid)->update([
                'smsMethod' => 'POST',
                'smsUrl' => action([LeadReplyController::class, 'onSMSReply']),
                'voiceUrl' => action([LeadReplyController::class, 'onVoiceReply']),
            ]);
        }

        $application = $twilioClient->applications
            ->create([
                    "voiceMethod" => "POST",
                    "voiceUrl" => action([TwilioController::class, 'conference'], [
                        'companyId' => $this->id,
                        'agentId' => $this->id,
                    ]),
                    "friendlyName" => $this->name,
                ]
            );
        
        $this->twilio_app_sid = $application->sid;

        // echo "--------------------".$this->twilio_app_sid."<br/>";
        // dd($application);
        $this->save();
    }
}
