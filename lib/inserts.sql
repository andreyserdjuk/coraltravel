insert into `operator`(id, `name_ru`, `name_en`)
    values
        (1, 'CoralTravel', 'CoralTravel');

insert into `ct_parsing_task`(id, `name`, `enabled`)
    values
        (1, 'updateCountry', false),                /*  CoralTravel::ID_UPDATE_COUNTRY                  */
        (2, 'updateRegion', false),                 /*  CoralTravel::ID_UPDATE_REGION                   */
        (3, 'updateArea', false),                   /*  CoralTravel::ID_UPDATE_AREA                     */
        (4, 'updatePlace', false),                  /*  CoralTravel::ID_UPDATE_PLACE                    */
        (5, 'updateHotelCategoryGroup', false),     /*  CoralTravel::ID_UPDATE_HOTEL_CATEGORY_GROUP     */
        (6, 'updateHotelCategory', false),          /*  CoralTravel::ID_UPDATE_HOTEL_CATEGORY           */
        (7, 'updateHotel', false),                  /*  CoralTravel::ID_UPDATE_HOTEL                    */
        (8, 'updateRoomCategory', false),           /*  CoralTravel::ID_UPDATE_ROOM_CATEGORY            */
        (9, 'updateRoom', false),                   /*  CoralTravel::ID_UPDATE_ROOM                     */
        (10, 'updateMealCategory', false),          /*  CoralTravel::ID_UPDATE_MEAL_CATEGORY            */
        (11, 'updateMeal', false),                  /*  CoralTravel::ID_UPDATE_MEAL                     */
        (12, 'updateCurrency', false);              /*  CoralTravel::ID_UPDATE_CURRENCY                 */