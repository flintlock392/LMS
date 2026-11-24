<?php
/**
 * "Course / Program" drop-down with an "Other" choice that reveals a text box.
 * Submits  course = one of UserModel::COURSES or "__other",  and  course_other = the typed course.
 *
 * @var string $current      the saved course (when editing), '' for a new student
 * @var string $selectClass  CSS class for the <select>
 * @var string $inputClass   CSS class for the text box
 * @var bool   $showStar     add " *" to the label
 */
use App\Models\UserModel;

$current     ??= '';
$selectClass ??= 'form-select';
$inputClass  ??= 'form-control';
$showStar    ??= true;

$listed  = in_array($current, UserModel::COURSES, true);
$choice  = (string) old('course', $listed ? $current : ($current !== '' ? UserModel::OTHER_COURSE : ''));
$other   = (string) old('course_other', $listed ? '' : $current);
$isOther = $choice === UserModel::OTHER_COURSE;
?>
<label class="form-label" for="course">Course / Program<?= $showStar ? ' *' : '' ?></label>
<select class="<?= esc($selectClass) ?>" id="course" name="course" required
        onchange="var o=document.getElementById('course-other-wrap'),i=document.getElementById('course_other');o.hidden=this.value!=='<?= UserModel::OTHER_COURSE ?>';i.required=!o.hidden;if(!o.hidden){i.focus();}">
    <option value="" disabled <?= $choice === '' ? 'selected' : '' ?>>Choose a course…</option>
    <?php foreach (UserModel::COURSES as $c): ?>
        <option value="<?= esc($c) ?>" <?= $choice === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
    <?php endforeach; ?>
    <option value="<?= UserModel::OTHER_COURSE ?>" <?= $isOther ? 'selected' : '' ?>>Other (type the course)</option>
</select>
<div id="course-other-wrap" class="mt-2" <?= $isOther ? '' : 'hidden' ?>>
    <label class="form-label visually-hidden" for="course_other">Other course</label>
    <input class="<?= esc($inputClass) ?>" id="course_other" name="course_other" maxlength="100"
           placeholder="Type the full course name, e.g. Bachelor of Arts in Communication"
           value="<?= esc($other) ?>" <?= $isOther ? 'required' : '' ?>>
</div>
