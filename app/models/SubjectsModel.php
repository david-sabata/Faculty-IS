<?php


/**
 * Description of SubjectsModel
 *
 * @author David Šabata
 */
class SubjectsModel {

   /** flag for filtering the subjects by the current ac. year */
   const CURRENT_ACADEMIC_YEAR = 'current';


   /**
    * Returns datasource of all subjects
    * @return DibiDataSource
    */
   public function getSubjects() {
      $ds = \dibi::dataSource("
         SELECT s.*, (SELECT COUNT(*) FROM students_in_subjects WHERE id = s.id) students
         FROM subjects s
      ");

      return $ds;
   }

   /**
    * Returns datasource of sbjects garanted/tought/assisted by specified user
    * @param string $login
    * @return DibiDataSource
    */
   public function getSubjectsByTeacher($login) {
      $ds = \dibi::dataSource("
         SELECT s.*
         FROM subjects s
         JOIN teachers_in_subjects tis ON tis.id = s.id
         WHERE tis.login = %s", $login
      );

      return $ds;
   }


   /**
    * Returns datasource of sbjects the student is registered to, sorted by year desc.
    * @param string $login
    * @param int $academicYear for example 2010 for 2010/2011
    * @return DibiFluent
    */
   public function getSubjectsByStudent($login, $academicYear = NULL) {
      $df = dibi::select('s.*')
         ->from('subjects s')
         ->join('students_in_subjects sis')->on('sis.id = s.id')
         ->where('sis.login = %s', $login);

      if ($academicYear !== NULL && $academicYear == self::CURRENT_ACADEMIC_YEAR)
         $df = $this->filterCurrentYear($df);
      elseif ($academicYear !== NULL)
         $df = $df->where('year >= ', $academicYear, 'AND year <= %i', ($academicYear+1));

      $df = $df->orderBy('year DESC, code ASC');

      return $df;
   }


   /**
    * Returns the number of students who registered for the subject
    * @param int $subjectId
    * @return int
    */
   public function getStudentsCount($subjectId) {
      return dibi::fetchSingle("
         SELECT COUNT(*)
         FROM students_in_subjects sis
         WHERE sis.id = %i", $subjectId
      );
   }


   /**
    * Returns array of years in which at least one subject was tought
    * @return array
    */
   public function getYears() {
      $q = dibi::query("
         SELECT DISTINCT year
         FROM subjects
         ORDER BY year
      ");
      return $q->fetchPairs('year', 'year');
   }


   /**
    * Returns single subject by id
    * @param int $id
    * @return DibiRow
    */
   public function getSubject($id) {
      return dibi::fetch("
         SELECT s.*,
         (
            SELECT GROUP_CONCAT(t.login)
            FROM teachers_in_subjects t
            WHERE t.id = s.id
          ) teachers
         FROM subjects s
         WHERE s.id = %i", $id
      );
   }


   /**
    * Returns subjects by years
    * @param array|NULL $years
    * @return array
    */
   public function getSubjectsByYear($years) {
      $q = dibi::query("
         SELECT *
         FROM subjects
         %if", !empty($years), "WHERE year IN %in", $years, "%end
         ORDER BY year
      ");

      return $q->fetchAssoc('year|id');
   }

   /**
    * Creates an empty subject in actual year
    * @return int id of the new record
    */
   public function add() {
      \dibi::query("INSERT INTO subjects SET year = NOW()");
      return \dibi::insertId();
   }


   /**
    * Saves the subject (assumes the data has already been validated)
    * @param int|NULL $id  NULL for adding new record
    * @param array $data
    * @return bool
    */
   public function save($id, array $data) {

      if ($id === NULL)
         $id = $this->add();

      try {
         \dibi::query("UPDATE subjects SET", $data, "WHERE id=%i", $id);
      } catch (DibiDriverException $e) {
         return FALSE;
      }

      return TRUE;
   }



   public function setTeachers($subjectId, array $users) {
      $set = array();
      foreach ($users as $u)
         $set[] = array('id' => $subjectId, 'login' => $u);

      dibi::query("DELETE FROM teachers_in_subjects WHERE id = %i", $subjectId);
      dibi::query("INSERT INTO teachers_in_subjects %ex", $set);
      $cache = Nette\Environment::getCache('subjectsModel');
      $cache->clean(array(Nette\Caching\Cache::ALL => TRUE));
   }


   /**
    * Deletes the subject
    * @param int $id
    * @return bool
    */
   public function delete($id) {
      try {
         dibi::query("DELETE FROM subjects WHERE id=%i", $id);
      } catch (DibiDriverException $e) {
         return FALSE;
      }

      return TRUE;
   }


   /**
    * Is the subject tought by the specified user?
    * @param int $subjectId
    * @param string $login
    * @return bool
    */
   public static function isToughtBy($subjectId, $login) {
      $cache = Nette\Environment::getCache('subjectsModel');
      if (!isset($cache['toughtBy'])) {
         $tought = dibi::fetchPairs("
            SELECT CONCAT(login, '-', id), 1
            FROM teachers_in_subjects            
         ");
         $cache->save('toughtBy', $tought);
      }
      return isset($cache['toughtBy'][$login.'-'.$subjectId]) && $cache['toughtBy'][$login.'-'.$subjectId] == 1;
   }

   

   /**
    * Adds a condition to the query to show only subjects in actual academic year.
    * For this purpose academic year starts on 1.8.
    * @param DibiFluent $df
    * @return DibiFluent
    */
   public function filterCurrentYear($df) {
      if (!($df instanceof DibiFluent))
         throw new Exception('Argument #1 has to be type of DibiFluent');

      if (date('n') >= 8)
         return $df->where('year >= YEAR(CURDATE()) AND year <= YEAR(CURDATE())+1');
      else
         return $df->where('year >= YEAR(CURDATE())-1 AND year <= YEAR(CURDATE())');
   }



   /**
    * Registers/unregisters subjects for given student
    * Does NOT check other constrains than these defined in db!
    * @param string $login
    * @param array $toRegister
    * @param array|NULL $toUnregister
    */
   public function updateStudentSubjects($login, $toRegister, $toUnregister) {

      // unregister also subjects to be registered and prevent duplicated records
      $toUnregister = array_merge($toUnregister, $toRegister);

      if (!empty($toUnregister))
         dibi::query("
            DELETE FROM students_in_subjects
            WHERE login = %s", $login, " AND id IN %l", $toUnregister
         );

      // reorganize array for db query
      $insert = array();
      foreach ($toRegister as $id)
         $insert[] = array('login' => $login, 'id' => $id);

      if (!empty($insert))
         dibi::query("
            INSERT INTO students_in_subjects
            %ex", $insert
         );
   }


   /**
    * Finds ID of subject with $code tought in $year
    * @param string $code
    * @param int $year
    * @return int|NULL
    */
   public function lookupId($code, $year) {
      return dibi::fetchSingle("
         SELECT id
         FROM subjects
         WHERE year=%i", $year, "AND code=%s", $code
      );
   }

}
?>
