========================================CODE REFACTOR========================================

########## Strengths ##########
1. Proper separation of concerns with repository pattern.
2. Clear naming conventions for methods, making the purpose of each method easy to understand.
3. Dependency injection used for `BookingRepository` promotes testability and decoupling.

########## Weaknesses ##########
1. Repetitive Code
2. Lack of Validation
3. Poor Exception Handling
4. Magic Strings: jobid and admincommen
5. Environmental Dependency: env
6. Formatting Issues

########## Suggestions ##########
- Introduce Form Requests for validation
- Extract repetitive logic into helper methods or services
- Replace hardcoded values with constants
- Enhance error handling by logging exceptions and returning consistent error responses
- Improve adherence to RESTful conventions
- Standardize response formats use JSON returns