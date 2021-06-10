# KOU Letter Grade Calculator, KOUBS Istatistik Wrapper & RESTful API

https://kou-letter-grade-calculator.herokuapp.com/

```
! Documentation has not completed yet.
```

## API Routes

### Get Academic Terms [GET]
Get academic terms that exists in KOUBS Istatistik database.

```no-highlight
/api/academic-terms
```

### Get Faculties [GET]
Get faculties.

```no-highlight
/api/faculties
```

### Get Schools [GET]
Get schools.

```no-highlight
/api/schools
```

### Get Vocational Schools [GET]
Get vocational schools.

```no-highlight
/api/vocational-schools
```

### Get All Departments [GET]
Get all departments.

```no-highlight
/api/departments
```

### Get Specific Departments [GET]
Get specific departments.

```no-highlight
/api/departments/{unitKey}/{facultyKey}
```
Example: Get Faculty of Engineering Departments
```no-highlight
/api/departments/1/02 
```

### Get Courses Of Department [GET]
Get courses of deparment.

```no-highlight
/api/courses/{departmentKey}[/{academicTermKey}]
```

### Get Course Data And Calculate Letter Grade [POST]

```no-highlight
/api/course
```


