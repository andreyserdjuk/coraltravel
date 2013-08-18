insert into `operator`(id, `name_ru`, `name_en`)
    values
        (1, 'CoralTravel', 'CoralTravel');

insert into `ct_parsing_task`(id, `name`, `enabled`)
    values
        (1, 'updateCountry', 1),                /*  CoralTravel::ID_UPDATE_COUNTRY                  */
        (2, 'updateRegion', 1),                 /*  CoralTravel::ID_UPDATE_REGION                   */
        (3, 'updateArea', 1),                   /*  CoralTravel::ID_UPDATE_AREA                     */
        (4, 'updatePlace', 1),                  /*  CoralTravel::ID_UPDATE_PLACE                    */
        (5, 'updateHotelCategoryGroup', 1),     /*  CoralTravel::ID_UPDATE_HOTEL_CATEGORY_GROUP     */
        (6, 'updateHotelCategory', 1),          /*  CoralTravel::ID_UPDATE_HOTEL_CATEGORY           */
        (7, 'updateHotel', 1),                  /*  CoralTravel::ID_UPDATE_HOTEL                    */
        (8, 'updateRoomCategory', 1),           /*  CoralTravel::ID_UPDATE_ROOM_CATEGORY            */
        (9, 'updateRoom', 1),                   /*  CoralTravel::ID_UPDATE_ROOM                     */
        (10, 'updateMealCategory', 1),          /*  CoralTravel::ID_UPDATE_MEAL_CATEGORY            */
        (11, 'updateMeal', 1),                  /*  CoralTravel::ID_UPDATE_MEAL                     */
        (12, 'updateCurrency', 1);              /*  CoralTravel::ID_UPDATE_CURRENCY                 */