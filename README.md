# KOU Letter Grade Calculator, KOUBS Istatistik Wrapper & Web API


##### In prod. https://kou-letter-grade-calculator.herokuapp.com/


## Docker (Development)

```no-highlight
docker-compose up
```


# Web API

## Authorization

Currently, all requests don't require authentication.

## Responses
```javascript
{
  "success" : bool,
  "data"    : string | array
}
```

The `success` attribute describes if the transaction was successful or not.
The `data` attribute contains any other metadata associated with the response. This will be an escaped string containing JSON data if successful or an empty array if fails.

## Status Codes

API returns the following status codes:

| Status Code | Description |
| :--- | :--- |
| 200 | `OK` |
| 400 | `BAD REQUEST` |
| 404 | `NOT FOUND` |
| 500 | `INTERNAL SERVER ERROR` |


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


