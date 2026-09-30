---
noteId: "2438b210bcf911f1b0d837b196dd4a0c"
tags: []

---

# تقرير التدقيق النهائي — منظومة الإعدادات بعد الإصلاحات

## 1. Settings Security Audit

| السطح | الحالة | الدليل |
|---|---|---|
| كتابة مفاتيح اعتباطية عبر HTTP | **مغلقة** | `UpdateSettingsRequest` → مفتاح غير مسجّل = `prohibited` + قيم لا تطابق النوع = 422 (`SettingsGovernanceTest` يثبت الرفض) |
| كتابة برمجية `set('random', x)` | **مغلقة** | `SettingService::assertWritable` → `InvalidArgumentException` قبل أي استعلام |
| كتابة مفتاح Landlord من سياق Tenant | **مغلقة** | `canWrite()` يفحص `owner` مقابل `Tenant::checkCurrent()` — مثبت اختبارياً |
| `POST /settings` بلا دور | **مغلقة** | `role:Owner|Admin` — Member يحصل 403 (كان يكتب بنجاح سابقاً) |
| `/landlord/settings` على دومين مستأجر | **مغلقة** | بوابة `landlord` على كل مجموعات `landlord/*` (Settings + Subscription + Landlord) |
| تجاوز الـ registry | **نقطة واحدة متبقية**: الـ provisioner يكتب `TenantSetting::updateOrCreate` مباشرة داخل `execute()` — مسار داخلي مقصود، لكنه يلتف على الإبطال والتدقيق | `TenantProvisioner` |

## 2. Settings Cache Audit

- **البنية**: `settings.map.{landlord|tenant.{id}}.{domain}` عبر `rememberForever` — يلغي ~8 استعلامات/طلب.
- **الإبطال**: `set()` ينسف خريطة النطاق الحالي فقط — صحيح لأن الدمج landlord+tenant يحدث عند القراءة من خريطتين منفصلتين → كتابة landlord لا تلوّث خريطة tenant والعكس.
- **فجوة مؤكدة**: الكتابات المباشرة عبر `TenantSetting::updateOrCreate` (الـ provisioner) **لا تُبطِل الكاش** — إذا قُرئت خريطة مستأجر قبل البذر تبقى قديمة إلى الأبد. الخطورة عملية منخفضة (لا طلبات متزامنة أثناء التهيئة) لكنها ثغرة اتساق.
- **لا تسريب بين مستأجرين**: مفاتيح الكاش مفصولة بـ `tenant.{id}` — مستحيل بنيوياً أن يقرأ مستأجر كاش آخر (حتى لو اشتركا بنفس الـ cache store).
- ملاحظة: `Cache::rememberForever` على driver `file`/`redis` يعني الإعدادات تُخزَّن دائماً — إبطالها الوحيد عبر `set()`؛ أي تعديل DB يدوي يحتاج `cache:clear` — مقبول وموثّق ضمنياً بالتصميم.

## 3. Theme Consumption Audit — سلسلة كاملة

```
getTheme() [session > tenant > landlord > enum default]
→ share.theme {theme,palette,mode,palettes,font}
→ app.blade.php: data-theme/data-mode SSR + theme-init (FOUC، يحل 'system' قبل أول رسم)
→ setupInertiaStateBridge: initTheme أول رسم + router.on('navigate') لكل انتقال
→ useThemeStore → documentElement attrs → tokens في app.css
```

- **كل الصفحات/المكونات تقرأ من نفس المصدر** — فحص الهكس الثابت: صفر تلوين خام في أي صفحة Vue؛ الهكس الوحيد في `THEME_PRESETS` (سجل العينات الشرعي، صار الآن مُصفّى بـ `theme.palettes` المشترك من `ThemePalette` enum — مصدر PHP واحد).
- إعدادات المستأجر/المنصة لم تعد تكتب جلسة — التفضيل المؤقت (`ThemeController`) والافتراضي الدائم منفصلان نظامياً.

## 4. Translation Coverage Audit

| المجموع | en | ar |
|---|---|---|
| مفاتيح مدمجة عالمياً | 406 | 406 |

- كل مفاتيح `t()` المستخدمة في كل الموديولات مغطاة: Access/Landlord/Settings/Subscription/Tenant = **صفر مفقود**. الاستثناءات في Core هي أسماء أحداث (`update:searchQuery`, `row-click`) وليست مفاتيح ترجمة.
- توازن en/ar متطابق (406=406).
- **ملاحظة هيكلية**: المفاتيح مشتركة عالمياً لا مملوكة لموديول — مفتاح مثل `save_changes` يُستهلك من 4 موديولات وتعريفه في ملف واحد فقط (Core) — يعمل لكنه هشّ إذا نُقل الملف.

## 5. Ownership & Precedence Matrix (الحالة بعد الإصلاح)

| Setting | Owner | Precedence | مصدر الحقيقة |
|---|---|---|---|
| branding.app_name / support_email | Landlord | landlord فقط | `settings` |
| branding.workspace_name / tagline | Tenant (يرث landlord للـ tagline) | tenant→landlord | `tenant_settings` + write-through→`tenants.name` |
| branding.logo_url | shared | tenant→landlord | مسجل، بلا UI كاتب (سطح مستقبلي جاهز) |
| theme.palette / mode | shared + تفضيل زائر | session→tenant→landlord | `tenant_settings`/`settings` |
| localization.* | Landlord | landlord فقط | `settings` |
| system.* | Landlord | landlord فقط | `settings` |
| billing.default_currency / default_plan_id | Landlord | landlord فقط | `settings` |

**الفصل مُثبت**: المستأجر يقرأ `tenant_settings` ثم يسقط على `settings` (وراثة افتراضيات — مقصودة)؛ الـ landlord يقرأ `settings` فقط؛ أي مفتاح `owner=landlord` يُرفض برمجياً وHTTP في سياق مستأجر. صفر مسار يعرض إعداد مستأجر لمستأجر آخر (قواعد منفصلة + كاش مفصول بالمعرّف).

## 6. تقرير الخلل — اتصال موديلات الأدوار (أقوى من قبل)

**الواقعة المؤكدة بالأدلة**:
- `config/permission.php` → `'role' => Spatie\Permission\Models\Role` — موديل بدون اتصال مخصص → يستخدم الافتراضي `landlord`.
- `TenantProvisioner::seedTenantInitialData` يستدعي `Role::findOrCreate('Owner','web')` داخل `$tenant->execute()` — لكن الموديل لا يتبع سياق المستأجر → **الأدوار تُكتب في جدول roles الخاص بالـ landlord**.
- الدليل القاطع: `landlord.roles = {Super Admin, Member, Owner}` — أدوار المستأجرين تسرّبت إلى قاعدة المنصة. وجدول `vendor_1.roles` له معرّفات مختلفة (`{1:Owner, 2:Admin, 3:Member}`) → `model_has_roles.role_id` له معنى مختلف في كل قاعدة → `assignRole` كان يربط معرّفات من قاعدة لأخرى (**تلوث صلاحيات متقاطع**).
- **الأثر**: صلاحيات أدوار المستأجر مكسورة جزئياً على المستأجرين الجدد، والـ landlord DB يتلوث بأدوار `web`. الـ `role:Owner|Admin` الجديد على `/settings` يعتمد على صفوف `model_has_roles`+`roles` في قاعدة المستأجر — يعمل إذا وُجدت الأدوار (الـ provisioner لم يكن يزرعها هناك).

**الإصلاح المقترح** (قرار معماري — لم يُنفّذ): موديلا `Role`/`Permission` مخصصان في Access أو Core بـ `UsesTenantConnection` + إعادة بذر أدوار `web` داخل كل قاعدة مستأجر، أو `Role::on('tenant')` صراحة في الـ provisioner. يحتاج هجرة بيانات للأدوار المتسربة من landlord.

## ملخص الحالة

مغلق: زرع مفاتيح اعتباطية، كتابة بدون صلاحية، تجاوز ملكية landlord/tenant، ازدواج المخازن (tenant.settings JSON)، خلط جلسة/افتراضي، عزل دومين landlord، scaffold الميت، مفاتيح ميتة، عملة مثبتة.

مفتوح للجولة القادمة: اتصال موديلات الأدوار (الأخطر)، كتابة الـ provisioner المباشرة الالتفافية على الكاش، إعدادات الشعار المعلنة بلا UI، لغة المستأجر، حدود الوسائط.

---

# تحليل معماري عميق — مراجعة النقاط الـ13 (ما بعد التدقيق)

## 1. ملكية Authorization — الخلل أوسع من الـ provisioner

فحص كل مسارات Role/Permission:

| الموقع | السياق | الاتصال الفعلي | سليم؟ |
|---|---|---|---|
| LandlordDatabaseSeeder (Super Admin/landlord) | landlord | landlord | صحيح |
| TenantProvisioner::seedTenantInitialData | tenant (execute) | **landlord** للأدوار / tenant للـ pivot | فساد متقاطع |
| DatabaseSeeder::runTenantSpecificSeeders | tenant | **landlord** | نفس الخلل |
| RoleController (/roles للمستأجر) | tenant | **landlord** | واجهة المستأجر تقرأ/تكتب جدول roles الخاص بالمنصة |
| TenantUserService ->with(roles) / assignRole | tenant | علاقة tenant / Role CRUD landlord | غير متسق |
| role: middleware | tenant | tenant عبر علاقة المستخدم | يعتمد على بيانات مُفسدة |

**الحل الجذري**: موديلا Role/Permission مخصصان في Modules/Access/Models، مسجّلان في `config/permission.php > models`، يعيدان `getConnectionName()` → `'tenant'` إذا `Tenant::checkCurrent()` وإلا `'landlord'`. القرار يرتبط بالسياق لا بنقطة الاستدعاء — يصحح كل المسارات (seeder/controller/middleware/jobs) آلياً. يتطلب هجرة: حذف أدوار `web` المتسربة من `landlord.roles` وإعادة بذرها في قواعد المستأجرين.

## 2. workspace_name — مصدرا حقيقة فعلاً

`tenants.name` هوية دومين (finder/slug/session)؛ `branding.workspace_name` اسم عرض مفترض؛ `set()` يكتبهما معاً وتحديث `tenants.name` المباشر يترك الإعداد قديماً — نفس فصام `tenant.settings` بنكهة أخف.
**الحل**: مصدر واحد = `tenants.name`. إسقاط `workspace_name` من الـ registry و`getBranding`؛ نموذج "Display Name" للمستأجر يحدّث `Tenant::update(['name'])` مباشرة.

## 3. التفاف البذر — فجوة حوكمة لا كاش

`TenantSetting::updateOrCreate` في TenantProvisioner + DatabaseSeeder + `Setting::updateOrCreate` في LandlordDatabaseSeeder — ثلاثة مسارات تتجاوز validation/ownership/إبطال بلا مبرر: `set()` داخل `execute()` يعمل (المفاتيح shared/tenant-owned). **الحل**: كل البذر عبر `set()`.

## 4. rememberForever — الإبطال يجب أن يكون بنيوياً

الكتّاب المؤكدون: LandlordDatabaseSeeder, TenantProvisioner, DatabaseSeeder + أي كتابة يدوية مستقبلية. **الحل**: أحداث `saved`/`deleted` على Setting/TenantSetting → إبطال خريطة النطاق تلقائياً — نقطة إنفاذ واحدة لا يمكن تجاوزها، تجعل كل مسار كتابة آمناً بنيوياً.

## 5. الترجمة — 406=406 توازن لا اكتمال

دليل جديد: `aria-label="Toggle navigation menu"` إنجليزي مثبت في LandlordLayout + TenantLayout. تغطية مفاتيح `t()` دقيقة لكنها لا تكشف النصوص الخام. **الحل**: lint للنصوص الصلبة في القوالب (عناصر/aria/placeholder/toast). ملكية الترجمة: Core يملك المفاتيح المشتركة، وكل موديول يملك دلالاته — اتفاقية `module.key` عند التصادم.

## 6. theme.mode — ثلاثة مفاهيم تحتاج عقداً

- `theme.*` في `settings` = افتراضي المنصة (يبذر المستأجرين).
- نفسه في `tenant_settings` = تكوين مساحة العمل (يتغلب).
- `session(theme/theme_mode)` = تفضيل الزائر العابر.

النموذج متسق؛ اللبس الباقي: `mode='system'` كافتراضي منصة — قرار domain: إما منعه في سياق landlord أو توثيقه "اتبع نظام العميل".

## 7. billing.default_currency — افتراضي إنشاء لا عملة منصة

`plans.currency` قيمة تعاقدية لكل خطة؛ الإعداد بذرة نماذج فقط — لا يجوز استخدامه لتنسيق اشتراكات قائمة. إن كان القصد "عملة منصة واحدة" فهذا قرار domain آخر (يفرض تفردها). يبقى مفيداً بشرط توثيق الدلالة.

## 8. default_plan_id — دورة حياة ناقصة

خطة معطّلة/محذوفة تترك FK نصياً معطلاً. المطلوب: (أ) `exists:plans,id` موجودة؛ (ب) تنظيف/رفض عند إبطال الخطة — hook غير موجود (فجوة domain)؛ (ج) سقوط "أول خطة فعالة" موجود كسلامة. الخطط landlord-owned → الملكية متسقة.

## 9. logo_url — ليس Setting

`Modules\Landlord\Models\Tenant` يطبق `HasMedia`/`InteractsWithMedia` فعلاً (landlord_media موجود). الشعار وسيط مملوك للكيان → `branding.logo_url` يجب أن يُشتق من `getFirstMediaUrl('logo')` لا مفتاح نصي حر. **الحل**: إسقاطه من الـ registry؛ getBranding يقرأ الوسيط عند وصول واجهة الرفع.

## 10. is_public — عقد حقيقي بلا إنفاذ

القصد المرجح: إعدادات قابلة للقراءة للزوار. `share()` يكشف branding/theme/locale للجميع عملياً. القرار: تعريفه كقائمة بيضاء للكشف داخل share() أو إسقاط العمود — لا يُفعَّل قبل وجود مستهلك.

## 11. maintenance_mode — قرار domain

المعنى المميز المحتمل: إيقاف أسطح المستأجرين مع إبقاء لوحة landlord — خاصية لا يغطيها `artisan down`. غير منفّذ → أُزيل من السيدر؛ إن أُرادت تُبنى كاملة (إعداد + middleware يستثني landlord).

## 12. دورة حياة الإعدادات — الفجوة الأهم

الدلالات غير معرّفة: tenant_settings لا يفرّق "غير مضبوط"/"null"/"معطّل"؛ الدمج يمنع العودة للوراثة. **التوصية الدنيا**: `unset(domain,key)` (يحذف صف tenant → يرث) + توثيق دلالات null. تأجيل: تدقيق/إصدار/تشفير حتى متطلب فعلي.

## 13. الأحكام المنقّحة

| الادعاء السابق | الحكم المنقّح |
|---|---|
| ازدواج المخازن مغلق | **جزئي** — tenants.name ↔ workspace_name ما زالا مصدرين (نقطة 2) |
| الثيم مغلق | **مستقر وظيفياً** — يحتاج عقداً مكتوباً (نقطة 6) |
| الكاش سليم | **جزئي** — يحتاج إبطالاً بنيوياً (نقطة 4) |
| الترجمة مكتملة | **تغطية مفاتيح فقط** — نصوص خام موجودة (نقطة 5) |
| Authorization مغلق | **مفتوح** — اتصال موديلات الأدوار أوسع (نقطة 1) |

## خطة الإصلاح المحدّثة (مرتبة)

| # | الأولوية | العمل | القرار المسبق المطلوب |
|---|---|---|---|
| 1 | P0 | موديلات Role/Permission بدينامية الاتصال + هجرة الأدوار المتسربة | اعتماد التصميم |
| 2 | P0 | أحداث إبطال الكاش على النموذجين | — |
| 3 | P1 | توجيه كل البذر عبر set() | — |
| 4 | P1 | توحيد هوية الاسم: مصدر واحد tenants.name | اعتماد القرار |
| 5 | P2 | unset()/دلالات الوراثة | — |
| 6 | P2 | إسقاط logo_url من الـ registry؛ قرار is_public/maintenance | قرار domain |
| 7 | P2 | عقد theme مكتوب + قرار mode='system' | قرار domain |
| 8 | P3 | lint نصوص الترجمة الخام + hook لتنظيف default_plan_id | — |

---

# Pre-Implementation Gate + الجولة الثانية من الإصلاحات (نُفّذت)

## نتائج الـ Gate (بالأدلة)

**Spatie internals**: `roles()` = morphToMany عبر `Config::roleModel()` → موديل مخصص بـ `getConnectionName()` ديناميكي يصحح السلسلة كاملة (pivot + role + permissions عبر `Config::permissionModel()` + Gate `checkPermissionTo`). لا شيء يفرض landlord صراحة — الافتراضي فقط.

**تناقض اكتُشف وأُدرج في الحل**: `permission.cache.key = "spatie.permission.cache"` مفتاح عالمي ثابت + `PrefixCacheTask` معطّل → كاش الصلاحيات كان سيسمّم بين السياقات حتى بعد إصلاح الموديلات. الحل: `ScopePermissionCacheTask` (SwitchTenantTask قائم) يضبط `registrar->cacheKey = ...tenant-{id}` / `.landlord` + `clearPermissionsCollection()`؛ والمفتاح الافتراضي `.landlord` يُضبط في `AppServiceProvider::register`.

**كتابات الإعدادات**: المسح الكامل يؤكد — صفر `DB::table(settings|tenant_settings)` أو query-builder updates؛ كل المسارات Eloquent → أحداث `saved`/`deleted` تغطيها جميعاً.

## ما نُفّذ في هذه الجولة

### P0 — Authorization (أغلق جذرياً)
- `Modules/Access/Models/Role` + `Permission` — اتصال ديناميكي: `tenant` عند `Tenant::checkCurrent()` وإلا `landlord`. مسجّلان في `config/permission.php > models` — كل الاستخدامات (seeder/controller/middleware/tinker) صحيحة آلياً.
- `ScopePermissionCacheTask` مسجّل في `switch_tenant_tasks` — كاش الصلاحيات معزول per-context.
- إصلاح البيانات: حذف `Member`/`Owner` (guard=web) من `landlord.roles`؛ إعادة بذر `Owner/Admin/Member` في قاعدتي المستأجرين؛ تحويل كل `use Spatie\Permission\Models\Role|Permission` لموديلاتنا في 5 ملفات.
- الإثبات: الاختبارات عادت لـ `assignRole` الطبيعي ونجحت — لم تعد هناك حاجة لكتابة pivot يدوية.

### P0 — كاش الإعدادات البنيوي
- `saved`/`deleted` على `Setting`/`TenantSetting` → `SettingService::forgetMapForModel` — **كل** مسار كتابة (seeders/provisioner/tinker/مستقبلي) يُبطِل الخريطة آلياً. مُثبت اختبارياً: كتابة مباشرة عبر الموديل تنعكس فوراً في `get()`.

### P1 — مسارات الكتابة المحكومة
- `LandlordDatabaseSeeder`, `TenantProvisioner`, `DatabaseSeeder::runTenantSpecificSeeders` — كلها الآن عبر `SettingManagerContract::set()` (registry + ownership + إبطال).
- `TenantProvisioner` يحقن `SettingManagerContract` بدل concrete.

### P1 — مصدر حقيقة واحد للهوية
- `branding.workspace_name` أُسقط من الـ registry؛ `tenants.name` هو المصدر الوحيد.
- `TenantSettingsController` يحدّث `Tenant::current()->name` مباشرة (حقل top-level)؛ `getBranding` يقرأ `tenant->name`؛ لا write-through، لا انحراف.
- صفوف `workspace_name`/`logo_url` القديمة حُذفت من قواعد المستأجرين.

### P2 — دورة الحياة والوسائط
- `unset(domain,key)` على العقد + الخدمة — حذف الصف يعيد الوراثة (مُثبت: landlord→override→unset→landlord). `null ≡ unset`.
- `logo_url` أُسقط من الـ registry؛ `getBranding` يشتق من `Tenant::getFirstMediaUrl('logo')` (HasMedia موجود فعلاً).
- `PlanController::update` — تعطيل الخطة المطابقة لـ `billing.default_plan_id` يمسح الإعداد → يسقط على أول خطة فعالة.

### P3 — الترجمة
- `aria-label` الثابت في الـ layoutين → `t('toggle_navigation')` مضاف للقاموسين (en=48/ar=48 في Core).

## القرارات المثبتة

| القرار | الحكم |
|---|---|
| `theme.mode='system'` | مسموح — افتراضي منصة مشروع = "اتبع نظام الزائر" |
| `is_public` | عمود باقٍ بلا عقد أمني — لا يُبنى endpoint عام قبل مستهلك |
| `maintenance_mode` | لا مفتاح بلا مستهلك — يُبنى كخاصية كاملة عند الحاجة |
| `default_currency` | افتراضي إنشاء نماذج فقط؛ `plans.currency` هي الحقيقة التعاقدية |
| `null` في الإعدادات | ≡ unset — لا قيم null مخزنة |

## النتائج النهائية

- **68/68 اختبار — 774 assertion — صفر فشل**
- `npm run build` نظيف
- عزل المستأجرين: مفاتيح كاش الإعدادات والصلاحيات مفصولة بالسياق؛ أدوار web لا تلمس قاعدة المنصة بعد الآن.

## المتبقي (P3 منخفض)

- فحص lint شامل للنصوص الخام في كل القوالب (عيّنة `aria-label` أُصلحت؛ قد توجد أخرى).
- `is_public` — إسقاط العمود أو تحويله عقداً فعلياً عند أول مستهلك عام.
- أدوار `landlord` للمستخدمين (Admin side) — حالياً `Super Admin` فقط؛ لم تُبنَ أدوار فرعية بعد.
